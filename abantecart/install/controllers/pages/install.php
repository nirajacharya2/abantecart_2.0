<?php

namespace install\controllers;

use abc\core\ABC;
use abc\core\engine\{
    AController, AForm, Registry
};
use abc\core\lib\{
    AException, AJson, ALanguageManager, AProgressBar
};

/**\
 * Class ControllerPagesInstall
 *
 * @property \install\models\ModelInstall $model_install
 */
class ControllerPagesInstall extends AController
{
    private $error = [];

    public function main()
    {
        $this->data = [];
        $run_level = $this->request->get['runlevel'];

        if (isset($run_level)) {
            if (!in_array((int) $run_level, [1, 2, 3, 4, 5])) {
                abc_redirect(
                    ABC::env('HTTPS_SERVER')
                    .'index.php?activation'
                    .'&admin_secret='.$this->request->post['admin_secret']
                );
            }

            if (!$this->session->data['install_step_data'] && (int) $run_level == 1) {
                if (filesize(ABC::env('DIR_CONFIG').'/enabled.config.php')) {
                    abc_redirect(ABC::env('HTTPS_SERVER').'index.php?activation');
                } else {
                    abc_redirect(ABC::env('HTTPS_SERVER').'index.php?rt=license');
                }
            }

            echo $this->runlevel((int) $run_level);
            return null;
        }

        if ($this->request->is_POST() && $this->_validate()) {
            $this->session->data['install_step_data'] = $this->request->post;
            //add parameter of URL to "public" subdirectory
            $this->session->data['install_step_data']['http_server'] = substr(
                    ABC::env('HTTPS_SERVER'),
                    0,
                    -9
                )
                ."/public/";
            abc_redirect(ABC::env('HTTPS_SERVER').'index.php?rt=install&runlevel=1');
        }

        //check is cart already installed
        if (is_file(ABC::env('DIR_CONFIG').'enabled.config.php')) {
            abc_redirect(ABC::env('HTTPS_SERVER').'index.php?rt=finish');
        }

        $this->data['error'] = $this->error;
        $this->data['action'] = ABC::env('HTTPS_SERVER').'index.php?rt=install';

        $fields = [
            'db_driver'        => [
                'mysql',
                'Select Database Driver',
            ],
            'db_host'          => [
                'localhost',
                'Enter Database Hostname',
            ],
            'db_user'          => [
                '',
                'Enter Database Username',
            ],
            'db_password'      => [
                '',
                'Enter Password, if any',
            ],
            'db_name'          => [
                '',
                'Enter Database Name',
            ],
            'db_prefix'        => [
                'abc_',
                'Add prefix to database tables',
            ],
            'username'         => [
                'admin',
                'Enter new admin username',
            ],
            'password'         => [
                '',
                'Enter Secret Admin Password',
            ],
            'password_confirm' => [
                '',
                'Repeat the password',
            ],
            'email'            => [
                '',
                'Provide valid email of administrator',
            ],
            'admin_secret'     => [
                '',
                'Enter your secret admin key',
            ],
        ];

        foreach ($fields as $field => $default_value) {
            if (isset($this->request->post[$field])) {
                $this->data[$field] = $this->request->post[$field];
            } else {
                $this->data[$field] = $default_value[0];
            }
        }

        $form = new AForm('ST');
        $form->setForm(
            [
                'form_name' => 'form',
                'update'    => '',
            ]
        );

        $this->data['form']['id'] = 'form';
        $this->data['form']['form_open'] = $form->getFieldHtml(
            [
                'type'   => 'form',
                'name'   => 'editFrm',
                'action' => $this->data['action'],
            ]
        );

        foreach ($fields as $field => $default_value) {
            if ($field != 'db_driver') {
                $this->data['form'][$field] = $form->getFieldHtml(
                    [
                        'type'        => (in_array($field, ['password', 'password_confirm']) ? 'password' : 'input'),
                        'name'        => $field,
                        'value'       => $this->data[$field],
                        'placeholder' => $default_value[1],
                        'required'    => in_array(
                            $field,
                            [
                                'db_host',
                                'db_user',
                                'db_name',
                                'username',
                                'password',
                                'password_confirm',
                                'email',
                                'admin_secret',
                            ]
                        ),
                    ]
                );
            } else {
                $options = [];

                if (extension_loaded('mysqli') || extension_loaded('pdo_mysql')) {
                    $options['mysql'] = 'MySQL';
                }

                if ($options) {
                    $this->data['form'][$field] = $form->getFieldHtml(
                        [
                            'type'     => 'selectbox',
                            'name'     => $field,
                            'value'    => $this->data[$field],
                            'options'  => $options,
                            'required' => true,
                        ]
                    );
                } else {
                    $this->data['form'][$field] = '';
                    $this->data['error'][$field] = 'No database support. '
                        .'Please install AMySQL or PDO_MySQL php extension.';
                }
            }
        }

        $this->view->assign('back', ABC::env('HTTPS_SERVER').'index.php?rt=settings');

        $this->addChild('common/header', 'header', 'common/header.tpl');
        $this->addChild('common/footer', 'footer', 'common/footer.tpl');

        $this->view->batchAssign($this->data);
        $this->processTemplate('pages/install.tpl');
    }

    protected function _validate()
    {
        $this->load->model('install');
        $result = $this->model_install->validateSettings($this->request->post);
        if (!$result) {
            $this->error = $this->model_install->error;
        }

        return $result;
    }

    public function runlevel($step)
    {
        ob_start();
        $this->load->library('json');
        try {
            if ($step == 2) {
                $this->_install_SQL();
                ob_clean();
                ob_end_clean();
                $this->response->addJSONHeader();
                return AJson::encode(['ret_code' => 50]);
            } elseif ($step == 3) {
                //NOTE: Create config as late as possible. This will prevent triggering finished installation
                $this->_configure();
                ob_clean();
                ob_end_clean();
                //wait for end of writing of file on disk (for slow hdd)
                sleep(3);

                $this->session->data['finish'] = 'false';
                $this->response->addJSONHeader();
                return AJson::encode(['ret_code' => 100]);
            } elseif ($step == 4) {
                // Load demo data
                if ($this->session->data['install_step_data']['load_demo_data'] == 'on') {
                    $this->_load_demo_data();
                }
                ob_clean();
                ob_end_clean();
                //Clean session for configurations. We do not need them any more
                unset($this->session->data['install_step_data']);
                $this->session->data['finish'] = 'false';
                $this->response->addJSONHeader();
                return AJson::encode(['ret_code' => 150]);
            } elseif ($step == 5) {
                ob_clean();
                ob_end_clean();
                //install is completed but we are not yet finished
                $this->session->data['finish'] = 'false';
                // Load languages with asynchronous approach
                $this->response->addJSONHeader();
                return AJson::encode(['ret_code' => 200]);
            }
        } catch (AException $e) {
            return $e->getMessage();
        }

        $this->view->assign('url', ABC::env('HTTPS_SERVER').'index.php?rt=install');
        $this->view->assign('redirect', ABC::env('HTTPS_SERVER').'index.php?rt=finish');
        $temp = $this->dispatch(
            'pages/install/progressbar_scripts',
            [
                'url' => ABC::env('HTTPS_SERVER').'index.php?rt=install/progressbar',
            ]
        );
        $this->view->assign('progressbar_scripts', $temp->dispatchGetOutput());

        $this->addChild('common/header', 'header', 'common/header.tpl');
        $this->addChild('common/footer', 'footer', 'common/footer.tpl');
        $this->processTemplate('pages/install_progress.tpl');

        return null;
    }

    private function _install_SQL()
    {
        $this->load->model('install');
        $this->model_install->RunSQL($this->session->data['install_step_data']);
        return null;
    }

    private function _configure()
    {
        $this->load->model('install');
        $this->model_install->configure($this->session->data['install_step_data']);

        return null;
    }

    public function _load_demo_data()
    {
        $this->load->model('install');
        $this->model_install->loadDemoData($this->session->data['install_step_data']);
        return null;
    }

    public function progressbar()
    {
        $this->load->model('install');
        $this->model_install->setADB($this->session->data['install_step_data']);

        // unlock session !important!
        session_write_close();
        try {
            $progress = new progressbar($this->registry);
            $this->response->addJSONHeader();
            switch ($this->request->get["work"]) {
                case "max":
                    echo AJson::encode(['total' => $progress->get_max()]);
                    exit;
                case "do":
                    $result = $progress->do_work();
                    if (!$result) {
                        $result = [
                            'status'    => 406,
                            'errorText' => $result,
                        ];
                        header('HTTP/1.1 402 Application Error');
                    } else {
                        $result = ['status' => 100];
                    }
                    echo AJson::encode($result);
                    exit;
                case "progress":
                    echo AJson::encode(['prc' => (int) $progress->get_progress()]);
                    exit;
            }
        } catch (AException $e) {
            header('HTTP/1.1 402 Application Error');
            echo $e->getMessage();
            exit;
        }
    }

    public function progressbar_scripts($url)
    {
        $this->view->assign('url', $url);
        $this->processTemplate('pages/progressbar.tpl');
    }
}

/** @noinspection PhpIncludeInspection */
require_once(ABC::env('DIR_LIB')."progressbar.php");

/*
 * Interface for progressbar
 */

class progressbar implements AProgressBar
{
    /**
     * @var Registry
     */
    private $registry;

    function __construct($registry)
    {
        $this->registry = $registry;
    }

    function get_max()
    {
        $language = new ALanguageManager($this->registry, 'en');
        $language_blocks = $language->getAllLanguageBlocks('english');
        $language_blocks['admin'] = array_merge(
            $language_blocks['admin'],
            $language_blocks['extensions']['admin']
        );
        $language_blocks['storefront'] = array_merge(
            $language_blocks['storefront'],
            $language_blocks['extensions']['storefront']
        );

        return sizeof($language_blocks['admin']) + sizeof($language_blocks['storefront']);
    }

    function get_progress()
    {
        $cnt = 0;
        $db = $this->registry->get('db');
        $res = $db->query(
            "SELECT section, COUNT(DISTINCT `block`) AS cnt
            FROM ".$db->table_name('language_definitions')."
            GROUP BY section"
        );
        foreach ($res->rows as $row) {
            $cnt += $row['cnt'];
        }

        return $cnt;
    }

    function do_work()
    {
        //do the trick for ALanguage
        ABC::env('INSTALL', false, true);
        $language = new ALanguageManager($this->registry, 'en');
        //Load default language (1) English on install only.
        return $language->definitionAutoLoad(1, 'all', 'all');
    }
}