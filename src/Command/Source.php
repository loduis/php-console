<?php

namespace PhpConsole\Command;

use Closure;
use Symfony\Component\Finder\SplFileInfo;

class Source
{
    protected $tokens;

    protected $count;

    protected $index = -1;

    /**
     * Get the command class for the file
     *
     * @param  string|array|\Symfony\Component\Finder\SplFileInfo $source
     *
     * @return void
     */

    public function __construct($source)
    {
        $source       = $this->getContents($source);
        $this->tokens = token_get_all($source);
        $this->count  = count($this->tokens);
    }

    /**
     * Get the command source from file
     *
     * @param  string|\Symfony\Component\Finder\SplFileInfo $file
     *
     * @return static
     */
    public static function fromFile($file)
    {
        if (!$file instanceof SplFileInfo) {
            $file = new SplFileInfo($file, '', '');
        }

        return new static($file);
    }

    /**
     * Get the command class for the file
     *
     * @param  string|\Symfony\Component\Finder\SplFileInfo $file
     *
     * @return string
     */
    protected function getContents($source)
    {
        if ($source instanceof SplFileInfo) {
            $source = $source->getContents();
        }

        if (is_array($source)) {
            $source = implode(PHP_EOL, $source);
        }

        if (strpos($source, '<?php') != 0) {
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

    protected function getTokenContent($possibleTokens, $start = null)
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

    protected function getTokenContentUntil($possibleTokens, $after = null)
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

    protected function getTokens($start = null)
    {
        $start = ($start ?: $this->index);
        for ($index = $start + 1; $index < $this->count; ++ $index) {
            yield $index => $this->getToken($index);
        }
    }

    protected function getToken($index)
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

    public function getNamespace()
    {
        $search = PHP_VERSION_ID < 80000 ? [T_STRING, T_NS_SEPARATOR] : T_NAME_QUALIFIED;
        if ($this->findToken(T_NAMESPACE) &&
            ($namespace = $this->getTokenContent($search))
        ) {
            if (strpos($namespace, '\\') !== 0) {
                $namespace = "\\$namespace";
            }

            return $namespace;
        }
    }

    public function getShortClassName()
    {
        if ($this->findToken([T_CLASS, T_TRAIT]) &&
            ($className = $this->getTokenContent(T_STRING))
        ) {
            return $className;
        }
    }

    public function getClassName()
    {
        $namespace = $this->getNamespace();

        if ($className = $this->getShortClassName()) {
            return $namespace . '\\' . $className;
        }
    }

    public function getProperty($name)
    {
        // Reset index to start from beginning for each property search
        $this->index = -1;
        
        $name = array_map(
            function ($name) {
                if (strpos($name, '$') !== 0) {
                    $name = '$' . $name;
                }
                return $name;
            },
            (array) $name
        );

        $where = function ($index) use ($name) {
            $varname = $this->getTokenContent(T_VARIABLE, $index);
            return $varname && in_array($varname, $name);
        };

        if ($this->findToken([T_PRIVATE, T_PROTECTED, T_PUBLIC], $where)) {
            $propertySource = $this->getTokenContentUntil(';', '=');
            return $propertySource;
        }

        return false;
    }
}

