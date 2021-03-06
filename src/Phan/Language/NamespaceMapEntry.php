<?php declare(strict_types=1);
namespace Phan\Language;

use Phan\Language\FQSEN\FullyQualifiedGlobalStructuralElement;

/**
 * Tracks a `use Foo\Bar;` statement at the top of a class.
 */
class NamespaceMapEntry implements \Serializable
{
    /**
     * @var FullyQualifiedGlobalStructuralElement
     */
    public $fqsen;

    /**
     * @var string the original case sensitive name of the use statement
     */
    public $original_name;

    /**
     * @var int the line number of the use statement
     */
    public $lineno;

    /**
     * @var bool
     */
    public $is_used = false;

    public function __construct(
        FullyQualifiedGlobalStructuralElement $fqsen,
        string $original_name,
        int $lineno
    ) {
        $this->fqsen = $fqsen;
        $this->original_name = $original_name;
        $this->lineno = $lineno;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            get_class($this->fqsen),
            (string)$this->fqsen,
            $this->original_name,
            $this->lineno,
            $this->is_used
        ]);
    }

    /**
     * @param string $representation
     */
    public function unserialize($representation)
    {
        list($fqsen_class, $fqsen, $this->original_name, $this->lineno, $this->is_used) = unserialize($representation);
        if (!is_string($fqsen_class) || !is_subclass_of($fqsen_class, FullyQualifiedGlobalStructuralElement::class)) {
            // Should not happen
            throw new \RuntimeException("Not a global fqsen: class " . var_export($fqsen_class));
        }
        $this->fqsen = $fqsen_class::fromFullyQualifiedString($fqsen);
    }
}
