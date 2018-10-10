<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Lockery component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
 
namespace Ness\Component\Lockey\Format;

/**
 * Make a component aware of a LockTokenFormatter implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockTokenFormatterAwareInterface
{
    
    /**
     * Register the formatter
     * 
     * @param LockTokenFormatterInterface $formatter
     *   Lock token formatter
     */
    public function setFormatter(LockTokenFormatterInterface $formatter): void;
    
    /**
     * Get registered formatter
     * 
     * @return LockTokenFormatterInterface
     *   Lock token formatter
     */
    public function getFormatter(): LockTokenFormatterInterface;
    
}
