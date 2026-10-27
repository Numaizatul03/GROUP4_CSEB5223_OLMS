<?php

declare(strict_types=1);

/**
 * Class Name:  BusinessRuleException
 * Purpose:     Signal that an action broke a shop rule (not a programming error).
 * Description: Thrown by services for cases such as "not enough stock" or
 *              "this order can no longer be cancelled". Controllers catch it
 *              and show getMessage() to the user, so the message must be
 *              written for the user to read.
 */
class BusinessRuleException extends RuntimeException
{
}
