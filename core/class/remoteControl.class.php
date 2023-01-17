<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

define('PLUGIN_NAME', 'remoteControl');

class remoteControl extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*     * ***********************Methode static*************************** */

 
  /* Fonction appelé par Listener
   */
  public static function pullRefresh($_option) {
    log::add(PLUGIN_NAME, 'debug', 'pullRefresh started'. json_encode($_option));

    // /** @var designImgSwitch */
    // $eqLogic = self::byId($_option['id']);
    // if (is_object($eqLogic) && $eqLogic->getIsEnable() == 1) {
    //   log::add(PLUGIN_NAME, 'debug', 'pullRefresh action sur : '.$eqLogic->getHumanName());
    //   $eqLogic->computeLamp();
    // }
  }

  /*     * *********************Méthodes d'instance************************* */

  /**
   * @return listener
   */
  private function getListener() {
    log::add(PLUGIN_NAME, 'debug', 'getListener');

    return listener::byClassAndFunction(__CLASS__, 'pullRefresh', array('id' => $this->getId()));
  }

  private function removeListener() {
    // log::add(PLUGIN_NAME, 'debug', 'remove Listener');

    $listener = $this->getListener();
    if (is_object($listener)) {
        $listener->remove();
    }
  }

  private function setListener() {
    // log::add(PLUGIN_NAME, 'debug', 'setListener');

    if ($this->getIsEnable() == 0) {
        $this->removeListener();
        return;
    }

    $cmd_toggle = $this->getConfiguration('toggle_cmd');
    $cmd_toggle = str_replace('#', '', $cmd_toggle);
    $cmd_toggle = cmd::byId($cmd_toggle);
    if (!is_object($cmd_toggle)) {
      throw new Exception("toggle_cmd non renseigné");
    }

    $cmd_plus = $this->getConfiguration('plus_cmd');
    $cmd_plus = str_replace('#', '', $cmd_plus);
    $cmd_plus = cmd::byId($cmd_plus);
    if (!is_object($cmd_plus)) {
      throw new Exception("plus_cmd non renseigné");
    }

    $cmd_minus = $this->getConfiguration('minus_cmd');
    $cmd_minus = str_replace('#', '', $cmd_minus);
    $cmd_minus = cmd::byId($cmd_minus);
    if (!is_object($cmd_minus)) {
      throw new Exception("minus_cmd non renseigné");
    }

    $listener = $this->getListener();
    if (!is_object($listener)) {
      $listener = new listener();
      $listener->setClass(__CLASS__);
      $listener->setFunction('pullRefresh');
      $listener->setOption(array('id' => $this->getId()));
    }
    $listener->emptyEvent();
    $listener->addEvent($cmd_toggle->getId());
    $listener->addEvent($cmd_plus->getId());
    $listener->addEvent($cmd_minus->getId());
    
    $listener->save();
  }

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    // log::add(PLUGIN_NAME, 'debug', 'postSave');

    $this->setListener();
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
    $this->removeListener();
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*     * **********************Getteur Setteur*************************** */

}

class remoteControlCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  // Exécution d'une commande
  public function execute($_options = array()) {
    log::add(PLUGIN_NAME, 'info', ' **** execute ****');
  }

  /*     * **********************Getteur Setteur*************************** */

}
