<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Lockey component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
 
namespace Ness\Component\Lockey\Exception;

/**
 * When lock token cannot be restored from his json representation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenCorruptedException extends \Exception
{
    //    
}
