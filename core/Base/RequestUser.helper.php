<?php
/**
 * Request user helper
 *
 * The user an API key logged in as, for the rest of this request only. A
 * login's user is kept in the PHP session instead; an API key's never is, so
 * a revoked key stops working on its next request.
 *
 * PHP version 8.4
 *
 * @category   Helper
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-25
 */

namespace Core\Base\Helpers;

class RequestUser
{
  public static ?\Core\Users\Models\User $user = NULL;
}
