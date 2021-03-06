<?php
namespace App\Areas\User\Services;

use App\Models\Admin;
use ManaPHP\Identity\Adapter\Jwt;
use ManaPHP\Service;

class ResetPasswordTokenService extends Service
{
    const KEY_SCOPE = 'admin:user:account:reset_password';
    const TTL = 1800;

    /**
     * @var Jwt
     */
    protected $_jwt;

    public function __construct()
    {
        $this->_jwt = new Jwt(['key' => $this->crypt->getDerivedKey(self::KEY_SCOPE), 'ttl' => self::TTL]);
    }

    /**
     * @param $user_name
     *
     * @return string
     * @throws \ManaPHP\Model\NotFoundException
     */
    public function generate($user_name)
    {
        $admin = Admin::firstOrFail(['admin_name' => $user_name]);
        $data = ['admin_name' => $admin->admin_name];
        return $this->_jwt->encode($data);
    }

    /**
     * @param string $token
     *
     * @return bool|array
     */
    public function verify($token)
    {
        try {
            return $this->_jwt->decode($token);
        } catch (\Exception $exception) {
            return false;
        }
    }
}
