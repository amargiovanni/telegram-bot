<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class SafeMathCalculator
{
    private const OPERATORS = [
        '+' => ['precedence' => 1, 'associativity' => 'L'],
        '-' => ['precedence' => 1, 'associativity' => 'L'],
        '*' => ['precedence' => 2, 'associativity' => 'L'],
        '/' => ['precedence' => 2, 'associativity' => 'L'],
        '^' => ['precedence' => 3, 'associativity' => 'R'],
    ];

    /**
     * Safely evaluate a mathematical expression without using eval()
     * Uses the Shunting Yard algorithm for parsing
     */
    public function calculate(string $expression): float
    {
        // Clean the expression - remove whitespace
        $expression = preg_replace('/\s+/', '', $expression);

        // Validate that expression contains only allowed characters
        if (! preg_match('/^[0-9+\-*\/().\^]+$/', $expression)) {
            throw new InvalidArgumentException('Espressione contiene caratteri non validi');
        }

        // Tokenize the expression
        $tokens = $this->tokenize($expression);

        // Convert to Reverse Polish Notation using Shunting Yard
        $rpn = $this->shuntingYard($tokens);

        // Evaluate the RPN expression
        return $this->evaluateRPN($rpn);
    }

    /**
     * Tokenize the mathematical expression
     */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $number = '';

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            if (is_numeric($char) || $char === '.') {
                $number .= $char;
            } else {
                if ($number !== '') {
                    $tokens[] = ['type' => 'number', 'value' => (float) $number];
                    $number = '';
                }

                if (isset(self::OPERATORS[$char])) {
                    // Handle unary minus
                    if ($char === '-' && (empty($tokens) || end($tokens)['type'] === 'operator' || end($tokens)['type'] === 'left_paren')) {
                        $number = '-';
                    } else {
                        $tokens[] = ['type' => 'operator', 'value' => $char];
                    }
                } elseif ($char === '(') {
                    $tokens[] = ['type' => 'left_paren', 'value' => '('];
                } elseif ($char === ')') {
                    $tokens[] = ['type' => 'right_paren', 'value' => ')'];
                }
            }
        }

        if ($number !== '') {
            $tokens[] = ['type' => 'number', 'value' => (float) $number];
        }

        return $tokens;
    }

    /**
     * Convert infix notation to Reverse Polish Notation using Shunting Yard algorithm
     */
    private function shuntingYard(array $tokens): array
    {
        $output = [];
        $stack = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'number') {
                $output[] = $token;
            } elseif ($token['type'] === 'operator') {
                while (
                    ! empty($stack) &&
                    end($stack)['type'] === 'operator' &&
                    (
                        (self::OPERATORS[$token['value']]['associativity'] === 'L' &&
                            self::OPERATORS[$token['value']]['precedence'] <= self::OPERATORS[end($stack)['value']]['precedence']) ||
                        (self::OPERATORS[$token['value']]['associativity'] === 'R' &&
                            self::OPERATORS[$token['value']]['precedence'] < self::OPERATORS[end($stack)['value']]['precedence'])
                    )
                ) {
                    $output[] = array_pop($stack);
                }
                $stack[] = $token;
            } elseif ($token['type'] === 'left_paren') {
                $stack[] = $token;
            } elseif ($token['type'] === 'right_paren') {
                while (! empty($stack) && end($stack)['type'] !== 'left_paren') {
                    $output[] = array_pop($stack);
                }

                if (empty($stack)) {
                    throw new InvalidArgumentException('Parentesi non bilanciate');
                }

                array_pop($stack); // Remove the left parenthesis
            }
        }

        while (! empty($stack)) {
            if (end($stack)['type'] === 'left_paren') {
                throw new InvalidArgumentException('Parentesi non bilanciate');
            }
            $output[] = array_pop($stack);
        }

        return $output;
    }

    /**
     * Evaluate a Reverse Polish Notation expression
     */
    private function evaluateRPN(array $rpn): float
    {
        $stack = [];

        foreach ($rpn as $token) {
            if ($token['type'] === 'number') {
                $stack[] = $token['value'];
            } elseif ($token['type'] === 'operator') {
                if (count($stack) < 2) {
                    throw new InvalidArgumentException('Espressione non valida');
                }

                $b = array_pop($stack);
                $a = array_pop($stack);

                $result = match ($token['value']) {
                    '+' => $a + $b,
                    '-' => $a - $b,
                    '*' => $a * $b,
                    '/' => $b === 0 ? throw new InvalidArgumentException('Divisione per zero') : $a / $b,
                    '^' => $a ** $b,
                    default => throw new InvalidArgumentException('Operatore non supportato'),
                };

                $stack[] = $result;
            }
        }

        if (count($stack) !== 1) {
            throw new InvalidArgumentException('Espressione non valida');
        }

        return $stack[0];
    }
}
