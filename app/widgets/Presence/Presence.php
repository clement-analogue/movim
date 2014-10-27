<?php

/**
 * @package Widgets
 *
 * @file Logout.php
 * This file is part of MOVIM.
 * 
 * @brief The little logout widget.
 *
 * @author Guillaume Pasquet <etenil@etenilsrealm.nl>
 *
 * @version 1.0
 * @date 20 October 2010
 *
 * Copyright (C)2010 MOVIM project
 * 
 * See COPYING for licensing information.
 */

use Moxl\Xec\Action\Presence\Chat;
use Moxl\Xec\Action\Presence\Away;
use Moxl\Xec\Action\Presence\DND;
use Moxl\Xec\Action\Presence\XA;
use Moxl\Xec\Action\Presence\Unavaiable;
use Moxl\Stanza\Stream;
use Moxl\Xec\Action\Storage\Get;

class Presence extends WidgetBase
{
    
    function load()
    {
        $this->addcss('presence.css');
        $this->addjs('presence.js');
        $this->registerEvent('mypresence', 'onMyPresence');
    }
    
    function onMyPresence()
    {
        $html = $this->preparePresence();
        RPC::call('movim_fill', 'logout', $html);
        RPC::commit();
    }

    function onPostDisconnect($data)
    {
        RPC::call('movim_reload',
                       BASE_URI."index.php?q=disconnect");
    }
    
    function ajaxSetStatus($show)
    {
        // We update the cache with our status and presence
        $presence = Cache::c('presence');

        if($show == "boot") $show = $presence['show'];
        Cache::c(
            'presence', 
            array(
                'status' => $presence['status'],
                'show' => $show
                )
        );
        
        switch($show) {
            case 'chat':
                $p = new Chat;
                $p->setStatus($presence['status'])->request();
                break;
            case 'away':
                $p = new Away;
                $p->setStatus($presence['status'])->request();
                break;
            case 'dnd':
                $p = new DND;
                $p->setStatus($presence['status'])->request();
                break;
            case 'xa':
                $p = new XA;
                $p->setStatus($presence['status'])->request();
                break;
        }
    }
    
    function ajaxLogout()
    {
        $session = \Sessionx::start();
        $p = new Unavaiable;
        $p->setType('terminate')
          ->setRessource($session->ressource)
          ->setTo($this->user->getLogin())
          ->request();

        Stream::end();

        //RPC::call('movim_redirect', Route::urlize('disconnect')); 
        //RPC::commit();
    }

    function ajaxConfigGet() {
        $s = new Get;
        $s->setXmlns('movim:prefs')
          ->request();
    }

    // We get the server capabilities
    function ajaxServerCapsGet()
    {
        $session = \Sessionx::start();
        $c = new \Moxl\Xec\Action\Disco\Request;
        $c->setTo($session->host)
          ->request();
    }

        // We refresh the bookmarks
    function ajaxBookmarksGet()
    {
        $session = \Sessionx::start();
        $b = new \Moxl\Xec\Action\Bookmark\Get;
        $b->setTo($session->user.'@'.$session->host)
          ->request();
    }
    
    function preparePresence()
    {
        $txt = getPresences();
        $txts = getPresencesTxt();
    
        $session = \Sessionx::start();
        
        $pd = new \Modl\PresenceDAO();
        $p = $pd->getPresence($this->user->getLogin(), $session->ressource);
       
        $presencetpl = $this->tpl();
        $presencetpl->assign('p', $p);
        $presencetpl->assign('txt', $txt);
        $presencetpl->assign('txts', $txts);
        $presencetpl->assign('callchat',    $this->genCallAjax('ajaxSetStatus', "'chat'"));
        $presencetpl->assign('callaway',    $this->genCallAjax('ajaxSetStatus', "'away'"));
        $presencetpl->assign('calldnd',     $this->genCallAjax('ajaxSetStatus', "'dnd'"));
        $presencetpl->assign('callxa',      $this->genCallAjax('ajaxSetStatus', "'xa'"));
        $presencetpl->assign('calllogout',  $this->genCallAjax('ajaxLogout'));
        $html = $presencetpl->draw('_presence_list', true);

        return $html;
    }
}

?>
