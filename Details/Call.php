<?php declare(strict_types=1);

namespace Phact\Container\Details;

class Call implements CallInterface
{
    /**
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $arguments;

    public function __construct(string $method, array $arguments = [])
    {
        $this->method = $method;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
