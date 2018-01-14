<?php
namespace App\Admin\Rbac\Controllers;

use App\Admin\Models\Admin;

class UserController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            $builder = Admin::query()
                ->columns('admin_id, admin_name, status, login_ip, login_time, email, creator_name, created_time, updated_time')
                ->orderBy('admin_id DESC');

            $keyword = $this->request->get('keyword', 'trim');
            if ($keyword !== '') {
                if (strpos($keyword, '@')) {
                    $builder->whereContains('email', $keyword);
                } else {
                    $builder->whereContains(['admin_name', 'email'], $keyword);
                }
            }

            $builder->paginate(15);
            return $this->response->setJsonContent($this->paginator);
        }
    }

    public function lockAction()
    {
        if ($this->request->isPost()) {
            $admin_id = $this->request->get('admin_id');
            $admin = Admin::firstOrFail($admin_id);

            $admin->status = Admin::STATUS_LOCKED;
            $admin->updated_time = time();
            $admin->update();

            return $this->response->setJsonContent(0);
        }
    }

    public function activeAction()
    {
        if ($this->request->isPost()) {
            $admin_id = $this->request->get('admin_id');
            $admin = Admin::firstOrFail($admin_id);

            $admin->status = Admin::STATUS_ACTIVE;
            $admin->updated_time = time();
            $admin->update();

            return $this->response->setJsonContent(0);
        }
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            try {
                $admin_name = $this->request->get('admin_name', '*|trim|account|minLength:4');
                $email = $this->request->get('email', '*|trim|email');
                $password = $this->request->get('password', '*|trim|password');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            if (Admin::exists(['admin_name' => $admin_name])) {
                return $this->response->setJsonContent('user is exists');
            }

            if (Admin::exists(['email' => $email])) {
                return $this->response->setJsonContent('email is exists');
            }

            $admin = new Admin();

            $admin->admin_name = $admin_name;
            $admin->email = $email;
            $admin->status = Admin::STATUS_ACTIVE;
            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($password, $admin->salt);
            $admin->creator_id = $this->userIdentity->getId();
            $admin->creator_name = $this->userIdentity->getName();
            $admin->created_time = $admin->updated_time = time();

            $admin->create();

            return $this->response->setJsonContent(0);
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            try {
                $admin_id = $this->request->get('admin_id', '*|int');
                $email = $this->request->get('email', 'email');
                $password = $this->request->get('password', 'password');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }
            $admin = Admin::firstOrFail($admin_id);
			
            if ($email !== '') {
                $admin->email = $email;
            }

            if ($password !== '') {
                $admin->salt = $this->password->salt();
                $admin->password = $this->password->hash($password, $admin->salt);
            }

            $admin->updated_time = time();

            $admin->update();

            return $this->response->setJsonContent(0);
        }
    }
}