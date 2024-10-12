<?php
namespace App\Logic\Providers;

use Illuminate\Support\Facades\Session;
use Facebook\PersistentData\PersistentDataInterface;

class MyLaravelPersistentDataHandler implements PersistentDataInterface
{
  /**
   * @var string Prefix to use for session variables.
   */
  protected $sessionPrefix = 'FBRLH_';

  /**
   * @inheritdoc
   */
  public function get($key)
  {
    $sessionKey = Session::get($this->sessionPrefix . $key);
    \Log::info("======GET========");
    \Log::debug($sessionKey);
    \Log::info("=======ENDGET=======");

    return $sessionKey;
  }

  /**
   * @inheritdoc
   */
  public function set($key, $value)
  {
    // dd($key,$value);
    \Log::info("======SET========");
    \Log::debug($value);
    \Log::info("=======ENDSET=======");
    Session::put($this->sessionPrefix . $key, $value);
  }
}