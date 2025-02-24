<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Info\Renderer;

use Flarum\Foundation\Info\RendererInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class CliRenderer implements RendererInterface
{
    public function __construct(protected OutputInterface $output)
    {
    }

    public function heading(string $title): void
    {
        $this->output->writeln("<bg=gray>$title</>");
    }

    public function keyValue(string $key, mixed $value): void
    {
        $this->output->writeln("<info>$key:</info> $value");
    }

    public function table(array $headers, array $rows): void
    {
        $table = (new Table($this->output))
            ->setHeaders($headers)->setStyle(
                (new TableStyle)->setCellHeaderFormat('<info>%s</info>')
            );

        foreach ($rows as $row) {
            $table->addRow($row);
        }

        $table->render();
    }

    public function open(): void
    {
    }

    public function close(): void
    {
    }
}
