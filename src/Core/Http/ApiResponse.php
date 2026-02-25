<?php

declare(strict_types=1);

namespace GetHost\Core\Http;

final class ApiResponse
{
    private int $statusCode;

    private string $status;

    private string $code;

    private string $message;

    /** @var array<string,mixed> */
    private array $data;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(int $statusCode, string $status, string $code, string $message, array $data = [])
    {
        $this->statusCode = $statusCode;
        $this->status = $status;
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return array<string,mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    public function toPlainText(): string
    {
        $lines = [
            'status: ' . $this->status,
            'code: ' . $this->code,
            'message: ' . $this->message,
        ];

        foreach ($this->flatten($this->data, 'data') as $line) {
            $lines[] = $line;
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string,mixed> $payload
     * @return string[]
     */
    private function flatten(array $payload, string $prefix): array
    {
        $out = [];
        foreach ($payload as $key => $value) {
            $full = $prefix . '.' . $key;
            if (is_array($value)) {
                if ($this->isList($value)) {
                    if ($value === []) {
                        $out[] = $full . ': []';
                        continue;
                    }

                    $allScalar = true;
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $allScalar = false;
                            break;
                        }
                    }

                    if ($allScalar) {
                        $out[] = $full . ': ' . implode(', ', array_map(
                            static fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (string)$v,
                            $value
                        ));
                        continue;
                    }

                    foreach ($value as $index => $item) {
                        $itemKey = $full . '[' . $index . ']';
                        if (is_array($item)) {
                            foreach ($this->flatten($item, $itemKey) as $line) {
                                $out[] = $line;
                            }
                        } else {
                            $out[] = $itemKey . ': ' . (is_bool($item) ? ($item ? 'true' : 'false') : (string)$item);
                        }
                    }
                    continue;
                }
                foreach ($this->flatten($value, $full) as $line) {
                    $out[] = $line;
                }
                continue;
            }

            $out[] = $full . ': ' . (is_bool($value) ? ($value ? 'true' : 'false') : (string)$value);
        }
        return $out;
    }

    /**
     * @param array<mixed> $value
     */
    private function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
