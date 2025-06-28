<?php

namespace PhpConsole\Command;

use Closure;
use Symfony\Component\Finder\SplFileInfo;

class Source
{
    protected array $tokens;

    protected int $count;

    protected int $index = -1;

    public function __construct(string|array|SplFileInfo $source)
    {
        $source       = $this->getContents($source);
        $this->tokens = token_get_all($source);
        $this->count  = count($this->tokens);
    }

    public static function fromFile(string|SplFileInfo $file): static
    {
        if (!$file instanceof SplFileInfo) {
            $file = new SplFileInfo($file, '', '');
        }

        return new static($file);
    }

    protected function getContents(string|array|SplFileInfo $source): string
    {
        if ($source instanceof SplFileInfo) {
            $source = $source->getContents();
        }

        if (is_array($source)) {
            $source = implode(PHP_EOL, $source);
        }

        if (!str_starts_with($source, '<?php')) {
            $source = '<?php ' . ltrim($source);
        }

        return $source;
    }

    protected function findToken(int | array $possibleTokens, ?Closure $where = null): bool | int
    {
        if ($hasWhere = ($where instanceof Closure)) {
            $where = Closure::bind($where, $this);
        }
        $possibleTokens = (array) $possibleTokens;
        foreach ($this->getTokens() as $index => $token) {
            if (in_array($token['search'], $possibleTokens) &&
                (!$hasWhere || ($where($index) && ($index = $this->index) !== -1))
            ) {
                return $this->index = $index;
            }
        }

        return false;
    }

    protected function getTokenContent(int|array $possibleTokens, ?int $start = null): ?string
    {
        $content       = null;
        $possibleTokens = (array) $possibleTokens;
        $endTokens     = ['{', ';', '('];
        foreach ($this->getTokens($start) as $index => $token) {
            if (($content !==null && $token['number'] == T_WHITESPACE) ||
                in_array($token['text'], $endTokens)
            ) {
                break;
            }
            if (in_array($token['number'], $possibleTokens)) {
                $content    .= $token['text'];
                $this->index = $index;
            }
        }

        return $content;
    }

    protected function getTokenContentUntil(string|array $possibleTokens, string|array|null $after = null): ?string
    {
        $content       = null;
        $possibleTokens = (array) $possibleTokens;
        $after         = (array) $after;
        foreach ($this->getTokens() as $index => $token) {
            if (in_array($token['search'], $possibleTokens)) {
                break;
            }
            if ($token['number'] == T_WHITESPACE ||
                ($after && in_array($token['search'], $after))
            ) {
                continue;
            }
            $content    .= $token['text'];
            $this->index = $index;
            $after       = null;
        }

        return $content === null ? $content: trim($content);
    }

    protected function getTokens(?int $start = null): \Generator
    {
        $start = ($start ?: $this->index);
        for ($index = $start + 1; $index < $this->count; ++ $index) {
            yield $index => $this->getToken($index);
        }
    }

    protected function getToken(int $index): array
    {
        $token = $this->tokens[$index];
        if (!is_array($token)) {
            $token = [null, $token];
        }

        return [
            'number' => $token[0],
            'text'   => $token[1],
            'search' => $token[0] === null ? $token[1] : $token[0]
        ];
    }

    public function getNamespace(): ?string
    {
        $search = PHP_VERSION_ID < 80000 ? [T_STRING, T_NS_SEPARATOR] : T_NAME_QUALIFIED;
        if ($this->findToken(T_NAMESPACE) &&
            ($namespace = $this->getTokenContent($search))
        ) {
            if (!str_starts_with($namespace, '\\')) {
                $namespace = "\\$namespace";
            }

            return $namespace;
        }

        return null;
    }

    public function getShortClassName(): ?string
    {
        if ($this->findToken([T_CLASS, T_TRAIT]) &&
            ($className = $this->getTokenContent(T_STRING))
        ) {
            return $className;
        }

        return null;
    }

    public function getClassName(): ?string
    {
        $namespace = $this->getNamespace();

        if ($className = $this->getShortClassName()) {
            return $namespace . '\\' . $className;
        }

        return null;
    }

    public function getProperty(string $name): string|false
    {
        // Reset index to start from beginning for each property search
        $this->index = -1;

        $name = array_map(
            fn($name) => str_starts_with($name, '$') ? $name : '$' . $name,
            (array) $name
        );

        $where = fn($index) => ($varname = $this->getTokenContent(T_VARIABLE, $index)) && in_array($varname, $name);

        if ($this->findToken([T_PRIVATE, T_PROTECTED, T_PUBLIC], $where)) {
            $propertySource = $this->getTokenContentUntil(';', '=');
            return $propertySource;
        }

        return false;
    }
}

