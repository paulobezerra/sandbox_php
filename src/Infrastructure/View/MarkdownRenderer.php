<?php
declare(strict_types=1);

namespace App\Infrastructure\View;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

final class MarkdownRenderer
{
    private MarkdownConverter $converter;
    public function __construct()
    {
        $environment=new Environment(['html_input'=>'escape','allow_unsafe_links'=>false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->converter=new MarkdownConverter($environment);
    }
    public function render(string $markdown): string { return $this->converter->convert($markdown)->getContent(); }
}
