<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2017 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\controllers\admin;

use abc\core\ABC;
use abc\core\engine\AController;

class ControllerPagesToolErrorLog extends AController
{
    public $data;

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData( $this, __FUNCTION__ );

        $this->loadLanguage( 'tool/error_log' );

        $this->data['main_url'] = $this->html->getSecureURL( 'tool/error_log');
        //TODO: solve issue with grants
        //if(ABAC::hasPermission('clear_log')) {
            $this->data['button_clear'] = $this->language->get('button_clear');
        //}

        if ( isset( $this->session->data['success'] ) ) {
            $this->data['success'] = $this->session->data['success'];
            unset( $this->session->data['success'] );
        } else {
            $this->data['success'] = '';
        }

        $filename = $this->request->get['filename'];
        if ( $filename && is_file( ABC::env( 'DIR_LOGS' ).$filename ) ) {
            $file = ABC::env( 'DIR_LOGS' ).$filename;
            $this->data['clear_url'] = $this->html->getSecureURL( 'tool/error_log/clearlog', '&filename='.$filename );
            $heading_title = $this->request->clean( $filename );
        } else {
            $args = ABC::getClassDefaultArgs( 'ALog' );
            $file = ABC::env( 'DIR_LOGS' ).$args[0]['app'];
            $filename = $args[0]['app'];
            $this->data['clear_url'] = $this->html->getSecureURL( 'tool/error_log/clearlog' );
            $heading_title = $this->language->get( 'heading_title' );
        }

        $all_logs = glob(ABC::env( 'DIR_LOGS' ).'{*.log,*.txt}', GLOB_BRACE);
        $options = [];
        foreach($all_logs as $f){
            $fileShortName = basename($f);
            $options[$fileShortName] = $fileShortName .' '.\H::human_filesize(filesize($f));
        }
        $this->view->assign(
            'log_list',
            $this->html->buildElement(
                [
                    'type'    => 'selectbox',
                    'name'    => 'filename',
                    'options' => $options,
                    'value'   => $filename
                ]
            )
        );

        $this->document->setTitle( $heading_title );
        $this->data['heading_title'] = $heading_title;
        $this->document->resetBreadcrumbs();
        $this->document->addBreadcrumb( [
            'href'      => $this->html->getSecureURL( 'index/home' ),
            'text'      => $this->language->get( 'text_home' ),
            'separator' => false,
        ]);
        $this->document->addBreadcrumb( [
            'href'      => $this->html->getSecureURL( 'tool/error_log', ( $filename ? '&filename='.$filename : '' ) ),
            'text'      => $heading_title,
            'separator' => ' :: ',
            'current'   => true,
        ]);

        if ( file_exists( $file ) ) {
            ini_set( "auto_detect_line_endings", true );

            $fp = fopen( $file, 'r' );

            // check filesize
            $filesize = filesize( $file );
            if ( $filesize > 500000 ) {
                $this->data['log'] = "\n\n\n\n###############################################################################################\n\n".
                    strtoupper( $this->language->get( 'text_file_tail' ) ).ABC::env( 'DIR_LOGS' )
                    ."###############################################################################################\n\n\n\n";
                fseek( $fp, -500000, SEEK_END );
                fgets( $fp );
            }
            $log = '';
            while ( ! feof( $fp ) ) {
                $log .= fgets( $fp );
            }
            fclose( $fp );
        } else {
            $log = '';
        }

        $log = htmlentities(str_replace(['<br/>', '<br />'], "\n", $log), ENT_QUOTES | ENT_IGNORE,
            ABC::env('APP_CHARSET'), true);
        //filter empty string
        $lines = array_filter( explode( "\n", $log ), 'strlen' );
        unset( $log );
        $k = 0;
        $data = [];
        foreach ( $lines as $line ) {
            if ( preg_match( '(^\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}:\d{2})', $line, $match ) ) {
                $k++;
                $data[$k] = str_replace( $match[0], '<b>'.$match[0].'</b>', $line );
            } else {
                $data[$k] .= '<br>'.$line;
            }
        }

        $this->data['log'] = $data;

        $this->view->batchAssign( $this->data );
        $this->processTemplate( 'pages/tool/error_log.tpl' );

        //update controller data
        $this->extensions->hk_UpdateData( $this, __FUNCTION__ );
    }

    public function clearLog()
    {

        //init controller data
        $this->extensions->hk_InitData($this,__FUNCTION__);

        $filename = $this->request->get['filename'];
        if( $filename && is_file(ABC::env('DIR_LOGS') . $filename) ){
            $file = ABC::env('DIR_LOGS') . $filename;
        }else {
            $args = ABC::getClassDefaultArgs('ALog');
            $file = ABC::env('DIR_LOGS').$args[0]['app'];
        }

        $handle = fopen($file, 'w+');
        if($handle !== false) {
            fclose($handle);
            $this->session->data['success'] = $this->language->get('text_success');
        }
        abc_redirect($this->html->getSecureURL('tool/error_log', '&filename='.$filename));

        //update controller data
        $this->extensions->hk_UpdateData($this,__FUNCTION__);
    }
}
