<?php
/**
 * Blockchain
 * Copyright 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Blockchain\Database;

use Generator;
use Blockchain\Exception\DatabaseException;
use Blockchain\Exception\NotFoundException;

/**
 * TODO: Add locking to last line etc
 */
class Filesystem
{
    /**
     * Reads a file
     *
     * @param string $path
     * @return string
     */
    public function read(string $path): string
    {
        $this->checkFileExists($path);
       
        $fh = fopen($path, 'r') ;
        defer($void, 'fclose', $fh);

        return fread($fh, filesize($path));
    }

    /**
     * Writes data to a file
     *
     * @param string $path
     * @param string $data
     * @param boolean $createDirectory
     * @return boolean
     */
    public function write(string $path, string $data, bool $createDirectory = false): bool
    {
        if ($createDirectory) {
            $directory = pathinfo($path, PATHINFO_DIRNAME);
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        $fh = fopen($path, 'w') ;
        defer($void, 'fclose', $fh);

        if ($fh === false || ! flock($fh, LOCK_EX)) {
            throw new DatabaseException('Error opening file');
        }

        return (bool) fputs($fh, $data);
    }

    /**
     * Appends a file
     *
     * @param string $path
     * @param string $data
     * @return boolean
     */
    public function append(string $path, string $data): bool
    {
        $fh = fopen($path, 'a');
        defer($void, 'fclose', $fh);

        if ($fh === false || ! flock($fh, LOCK_EX)) {
            throw new DatabaseException('Error opening file');
        }
      
        return (bool) fputs($fh, $data);
    }

    /**
     * Searches a file for a needle.
     *
     * @internal Uses forward searching since this is much faster as it is more efficient.
     *
     * Search for Block (forward)
     *  - index:1,294,690 took: 0.32834792137146
     *  - index:1 took:0.002582073211669
     * Search for Block (Backwards)
     *  - index:1 took:10.735311985016
     *
     * @param string $path
     * @param string $needle
     * @return string|null
     */
    public function search(string $path, string $needle): ?string
    {
        $this->checkFileExists($path);

        $out = null;

        $fh = fopen($path, 'r') ;
        defer($void, 'fclose', $fh);

        while (! feof($fh)) {
            $haystack = fgets($fh);
            if ($haystack && strpos($haystack, $needle) !== false) {
                $out = $haystack;
                break;
            }
        }
     
        return $out;
    }

    /**
     * Reads the last line of a file
     *
     * @param string $path
     * @param integer $buffer
     * @return string
     */
    public function lastLine(string $path, int $buffer = 4096): string
    {
        $this->checkFileExists($path);

        $fh = fopen($path, 'r');
        defer($void, 'fclose', $fh);

        fseek($fh, -$buffer, SEEK_END);
        $data = trim(fread($fh, $buffer));
        
        $linebreak = strrpos($data, PHP_EOL);
        if ($linebreak) {
            $data = substr($data, $linebreak + 1);
        }

        return $data;
    }

    /**
     * Yeilds a line of a file in reverse order
     *
     * @param string $path
     * @param integer $lines
     * @param integer $buffer
     * @return \Generator
     */
    public function each(string $path, int $lines = null, int $buffer = 100): Generator
    {
        $this->checkFileExists($path);
       
        $fh = fopen($path, 'r');
        defer($void, 'fclose', $fh);

        if ($fh === false || ! flock($fh, LOCK_SH)) {
            throw new DatabaseException('Error opening file');
        }

        fseek($fh, -1, SEEK_END);

        $pos = ftell($fh);

        while ($lines === null || $lines > 0) {
            $pos -= $buffer;
            if ($pos < 0) {
                $pos = 0;
            }

            fseek($fh, $pos);
            $line = fread($fh, $buffer);

            $linebreak = strrpos($line, PHP_EOL);
            if ($linebreak) {
                if ($pos === 0) {
                    $line = substr($line, 0, $linebreak);
                } else {
                    $line = substr($line, $linebreak + 1);
                    $pos += $linebreak;
                }
            }

            yield $line;

            $lines --;
            if ($pos === 0) {
                break;
            }
        }
    }

    /**
     * @param string $path
     * @return void
     */
    private function checkFileExists(string $path): void
    {
        if (! is_file($path)) {
            throw new NotFoundException('Not Found');
        }
    }
}
