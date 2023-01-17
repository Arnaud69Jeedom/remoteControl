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

    $eqLogic = self::byId($_option['id']);
    if (is_object($eqLogic) && $eqLogic->getIsEnable() == 1) {
      //log::add(PLUGIN_NAME, 'debug', 'pullRefresh action sur : '.$eqLogic->getHumanName());
      $eqLogic->computeLamp($_option);
    }
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

    $cmd_toggle = $this->getConfiguration('cmd_remote');
    $cmd_toggle = str_replace('#', '', $cmd_toggle);
    $cmd_toggle = cmd::byId($cmd_toggle);
    if (!is_object($cmd_toggle)) {
      throw new Exception("cmd_remote non renseigné");
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


  private function computeLamp($_option) {
    log::add(PLUGIN_NAME, 'debug', 'computeLamp');

    log::add(PLUGIN_NAME, 'debug', ' action='.$_option['value']);

    $cmd_lamp = $this->getConfiguration('cmd_lamp');
    $cmd_lamp = str_replace('#', '', $cmd_lamp);
    $cmd_lamp = cmd::byId($cmd_lamp);
    if (!is_object($cmd_lamp)) {
      log::add(PLUGIN_NAME, 'error', ' commande cmd_lamp non trouvé');
      throw new Exception("cmd_lamp non renseigné");
    }
    //log::add(PLUGIN_NAME, 'debug', ' cmd_lamp='.$cmd_lamp->getHumanName());
    $eqlogic_lamp = $cmd_lamp->getEqLogic();
    
    // toggle
    if ($_option['value'] == 'toggle') {
      $this->toggle($eqlogic_lamp);
    }

    // on
    if ($_option['value'] == 'on_press') {
      $this->turnOn($eqlogic_lamp);
    }

    // off
    if ($_option['value'] == 'off_press') {
      $this->turnOff($eqlogic_lamp);
    }

    // brightness_down
    if ($_option['value'] == 'down_press' ||
        $_option['value'] == 'brightness_down_click') {
      $this->brightness($eqlogic_lamp, -10);
    }

    // brightness_up
    if ($_option['value'] == 'up_press' ||
        $_option['value'] == 'brightness_up_click') {
      $this->brightness($eqlogic_lamp, +10);
    }

    // brightness_down_release
    if ($_option['value'] == 'down_hold' ||
        $_option['value'] == 'brightness_down_hold') {
      $this->brightness($eqlogic_lamp, -1);
    }

    // brightness_up_release
    if ($_option['value'] == 'up_hold' ||
        $_option['value'] == 'brightness_up_hold') {
      $this->brightness($eqlogic_lamp, +1);
    }
  }

  /**
   * Toggle une lampe
   */
  private function toggle($eqlogic_lamp) {
    // Recherche LIGHT_TOGGLE
    $cmd_toggle = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_TOGGLE');
    if ($cmd_toggle == null) {
      log::add(PLUGIN_NAME, 'error', ' commande LIGHT_TOGGLE non trouvée');
      throw new Exception("commande LIGHT_TOGGLE non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', ' cmd_toggle='.$cmd_toggle->getHumanName());
      
      $cmd_toggle->execCmd();
      return;
    }

    // Recherche LIGHT_STATE
    $lamp_state = 0;
    $cmd_state = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_STATE');
    if ($cmd_state == null) {
      log::add(PLUGIN_NAME, 'error', ' commande LIGHT_STATE non trouvée');      
      throw new Exception("commande LIGHT_STATE non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', ' cmd_state='.$cmd_state->getHumanName());
      $lamp_state = $cmd_state->execCmd();
      log::add(PLUGIN_NAME, 'debug', ' lamp_state='.$lamp_state);
    }

    if ($lamp_state == 0) {
      $this->turnOn($eqlogic_lamp);
    } else {
      $this->turnOff($eqlogic_lamp);
    }
  }

  /**
   * Allumer une lampe
   */
  private function turnOn($eqlogic_lamp) {
    // Recherche LIGHT_ON
    $cmd_lamp_on = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_ON');
    if ($cmd_lamp_on == null) {
      log::add(PLUGIN_NAME, 'error', ' commande LIGHT_ON non trouvée');
      throw new Exception("commande LIGHT_ON non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', ' cmd_lamp_on='.$cmd_lamp_on->getHumanName());
      $cmd_lamp_on->execCmd();
    }
  }

  /**
   * Eteindre une lampe
   */
  private function turnOff($eqlogic_lamp) {
    // Recherche LIGHT_OFF
    $cmd_lamp_off = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_OFF');
    if ($cmd_lamp_off == null) {
      log::add(PLUGIN_NAME, 'error', ' commande LIGHT_OFF non trouvée');
      throw new Exception("commande LIGHT_OFF non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', ' cmd_lamp_off='.$cmd_lamp_off->getHumanName());
      $cmd_lamp_off->execCmd();
    }
  }

  /**
   * Baisser la luminosité
   */
  private function brightness($eqlogic_lamp, $step) {
    $brightessValue = 0;
    // Recherche LIGHT_BRIGHTNESS
    $cmd_lamp_brightness = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_BRIGHTNESS');
    if ($cmd_lamp_brightness == null) {
      log::add(PLUGIN_NAME, 'error', ' commande LIGHT_BRIGHTNESS non trouvée');
      throw new Exception("commande LIGHT_BRIGHTNESS non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', ' cmd_lamp_brightness='.$cmd_lamp_brightness->getHumanName());
      $brightessValue = $cmd_lamp_brightness->execCmd();
      log::add(PLUGIN_NAME, 'debug', ' brightessValue='.$brightessValue);
    }
    $brightessValue = $brightessValue + $step;

    // Recherche LIGHT_SLIDER
    $cmd_lamp_slider = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_SLIDER');
    if ($cmd_lamp_slider == null) {
      log::add(PLUGIN_NAME, 'error', ' commande LIGHT_SLIDER non trouvée');
      throw new Exception("commande LIGHT_SLIDER non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', ' LIGHT_SLIDER='.$cmd_lamp_slider->getHumanName());
      $cmd_lamp_slider->execCmd(array('slider' => $brightessValue, 'transition' => 300));
      log::add(PLUGIN_NAME, 'debug', ' brightessValue='.$brightessValue);
    }

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
