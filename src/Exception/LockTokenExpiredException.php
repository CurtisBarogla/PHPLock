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
 * When lock token is expired for the user on the resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenExpiredException extends \Exception
{
    //
}
