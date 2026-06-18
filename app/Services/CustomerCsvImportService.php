<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerCsvImportService
{
    private const REQUIRED_HEADERS = ['name', 'phone_number', 'email', 'payment_amount'];

    public function import(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('The CSV file could not be read.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException('The CSV file is empty.');
        }

        $headers = array_map(fn (string $header): string => $this->normalizeHeader($header), $headers);
        $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

        if ($missingHeaders !== []) {
            fclose($handle);
            throw new RuntimeException('Missing CSV headers: '.implode(', ', $missingHeaders).'.');
        }

        $knownEmails = Customer::query()
            ->pluck('email')
            ->mapWithKeys(fn (string $email): array => [Str::lower($email) => true])
            ->all();

        $result = [
            'total_records' => 0,
            'inserted_records' => 0,
            'duplicate_records' => 0,
            'invalid_records' => 0,
            'validation_errors' => [],
        ];
        $lineNumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($this->isBlankRow($values)) {
                continue;
            }

            $result['total_records']++;
            $values = array_pad($values, count($headers), null);
            $row = array_combine($headers, array_slice($values, 0, count($headers)));

            $data = [
                'name' => trim((string) ($row['name'] ?? '')),
                'phone_number' => trim((string) ($row['phone_number'] ?? '')),
                'email' => Str::lower(trim((string) ($row['email'] ?? ''))),
                'payment_amount' => trim((string) ($row['payment_amount'] ?? '')),
                'payment_status' => 'Pending',
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255'],
                'payment_amount' => ['required', 'numeric', 'min:0'],
                'payment_status' => ['required', 'in:Pending,Paid'],
            ]);

            if ($validator->fails()) {
                $result['invalid_records']++;
                $result['validation_errors'][] = [
                    'line' => $lineNumber,
                    'errors' => $validator->errors()->toArray(),
                ];
                continue;
            }

            if (isset($knownEmails[$data['email']])) {
                $result['duplicate_records']++;
                continue;
            }

            Customer::query()->create($data);
            $knownEmails[$data['email']] = true;
            $result['inserted_records']++;
        }

        fclose($handle);

        return $result;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header));

        return Str::of($header)
            ->lower()
            ->replace([' ', '-'], '_')
            ->replaceMatches('/_+/', '_')
            ->toString();
    }

    private function isBlankRow(array $values): bool
    {
        return count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0;
    }
}
