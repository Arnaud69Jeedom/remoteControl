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
  const cmd_toggle_array = ['toggle', '0', '1'];  // 0 et 1 pour Enocean
  const cmd_on_array = ['on_press'];
  const cmd_off_array = ['off_press'];
  const cmd_brightness_down_array = ['down_press', 'brightness_down_click'];
  const cmd_brightness_up_array = ['up_press', 'brightness_up_click'];
  const cmd_brightness_down_hold_array = ['down_hold', 'brightness_down_hold'];
  const cmd_brightness_up_hold_array = ['up_hold', 'brightness_up_hold'];
  const cmd_color_up_array = ['arrow_right_click'];
  const cmd_color_down_array = ['arrow_left_click'];
  const cmd_color_up_hold_array = ['arrow_right_hold'];
  const cmd_color_down_hold_array = ['arrow_left_hold'];

  /*     * *************************Attributs****************************** */

  /*     * ***********************Methode static*************************** */

 
  /* Fonction appelé par Listener
   */
  public static function pullRefresh($_option) {
    $eqLogic = self::byId($_option['id']);
    log::add(PLUGIN_NAME, 'info', 'pullRefresh. Remote:'.$eqLogic->getHumanName().', value: '. $_option['value']);

    if (is_object($eqLogic) && $eqLogic->getIsEnable() == 1) {
      $eqLogic->computeLamp($_option);
    }
  }

  /*     * *********************Méthodes d'instance************************* */

  /**
   * @return listener
   */
  private function getListener() {
    //log::add(PLUGIN_NAME, 'debug', 'getListener');

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
    if ($this->getIsEnable() == 0) {
        $this->removeListener();
        return;
    }

    $remote_array = [];

    // Remote 1
    $cmd_toggle = $this->getConfiguration('cmd_remote');
    $cmd_toggle = str_replace('#', '', $cmd_toggle);
    $cmd_toggle = cmd::byId($cmd_toggle);
    if (is_object($cmd_toggle)) {
      array_push($remote_array, $cmd_toggle);
    }

    // Remote 2
    $cmd_toggle = $this->getConfiguration('cmd_remote2');
    $cmd_toggle = str_replace('#', '', $cmd_toggle);
    $cmd_toggle = cmd::byId($cmd_toggle);
    if (is_object($cmd_toggle)) {
      array_push($remote_array, $cmd_toggle);
    }

    // Remote 3
    $cmd_toggle = $this->getConfiguration('cmd_remote3');
    $cmd_toggle = str_replace('#', '', $cmd_toggle);
    $cmd_toggle = cmd::byId($cmd_toggle);
    if (is_object($cmd_toggle)) {
      array_push($remote_array, $cmd_toggle);
    }

    // Remote 4
    $cmd_toggle = $this->getConfiguration('cmd_remote4');
    $cmd_toggle = str_replace('#', '', $cmd_toggle);
    $cmd_toggle = cmd::byId($cmd_toggle);
    if (is_object($cmd_toggle)) {
      array_push($remote_array, $cmd_toggle);
    }

    if (empty($remote_array)) {
      return;
    }

    $listener = $this->getListener();
    if (!is_object($listener)) {
      $listener = new listener();
      $listener->setClass(__CLASS__);
      $listener->setFunction('pullRefresh');
      $listener->setOption(array('id' => $this->getId()));
    }
    $listener->emptyEvent();

    foreach($remote_array as $remote ) {
      //log::add(PLUGIN_NAME, 'debug', 'addEvent:'.$remote->getHumanName());
      $listener->addEvent($remote->getId());
    }
  
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
    $cmd_array = [];
    $cmd_doublon = '';

    try {
      $cmd = $this->getConfiguration('cmd_remote');
      if (!empty($cmd))
      {
        array_push($cmd_array, $cmd);
      }

      $cmd = $this->getConfiguration('cmd_remote2');
      if (!empty($cmd))
      {
        if (in_array($cmd, $cmd_array)) {
          $cmd_doublon = $cmd;
          throw new Exception('Commande en double : '. $cmd);
        }
        array_push($cmd_array, $cmd);
      }

      $cmd = $this->getConfiguration('cmd_remote3');
      if (!empty($cmd))
      {
        if (in_array($cmd, $cmd_array)) {
          $cmd_doublon = $cmd;
          throw new Exception('Commande en double : '. $cmd);
        }
        array_push($cmd_array, $cmd);
      }
    }
    catch (Exception $ex) {
      log::add(PLUGIN_NAME, 'info', 'Erreur lors de l\'enregistrement');

      $cmd = $cmd_doublon;
      $cmd = cmd::byId(str_replace('#', '', $cmd));
      if ($cmd != null) {
        $cmd = $cmd->getHumanName();
        log::add(PLUGIN_NAME, 'error', 'cmd:'.$cmd);
        throw new Exception('Commande en double : '.$cmd);
      } else {
        log::add(PLUGIN_NAME, 'error', 'cmd:'.$cmd);
        throw new Exception('Commande en double : '.$cmd_doublon);
      }      

    }
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
    log::add(PLUGIN_NAME, 'debug', '  computeLamp : '.$this->getHumanName().', commande: '. $_option['value']);

    $cmd_lamp = $this->getConfiguration('cmd_lamp');
    $cmd_lamp = str_replace('#', '', $cmd_lamp);
    $cmd_lamp = cmd::byId($cmd_lamp);
    if (!is_object($cmd_lamp)) {
      log::add(PLUGIN_NAME, 'error', ' commande cmd_lamp non trouvé');
      throw new Exception("cmd_lamp non renseigné");
    }
    //log::add(PLUGIN_NAME, 'debug', ' cmd_lamp='.$cmd_lamp->getHumanName());
    $eqlogic_lamp = $cmd_lamp->getEqLogic();
    
    // Valeur commande optionnelle
    $cmd_value = $this->getDefinedValue($_option);  //$this->getConfiguration('cmd_value');

    // toggle
    if (in_array($_option['value'], remoteControl::cmd_toggle_array) || 
        (!empty($cmd_value) && $_option['value'] == $cmd_value)
     ) {
      $this->toggle($eqlogic_lamp);
      return;
    }

    // on
    if (in_array($_option['value'], remoteControl::cmd_on_array)) {
      $this->turnOn($eqlogic_lamp);
      return;
    }

    // off
    if (in_array($_option['value'], remoteControl::cmd_off_array)) {
      $this->turnOff($eqlogic_lamp);
      return;
    }

    // brightness_down
    if (in_array($_option['value'], remoteControl::cmd_brightness_down_array)) {
      $this->brightness($eqlogic_lamp, -10, 'once');
      return;
    }
    // brightness_up
    if (in_array($_option['value'], remoteControl::cmd_brightness_up_array)) {
      $this->brightness($eqlogic_lamp, 10, 'once');
      return;
    }

    // brightness_down_hold
    if (in_array($_option['value'], remoteControl::cmd_brightness_down_hold_array)) {
      $this->brightness($eqlogic_lamp, -1, 'hold');
      return;
    }
    // brightness_up_hold
    if (in_array($_option['value'], remoteControl::cmd_brightness_up_hold_array)) {
      $this->brightness($eqlogic_lamp, 1, 'hold');
      return;
    }

    // couleur left
    if (in_array($_option['value'], remoteControl::cmd_color_down_array)) {
      $this->temperature_color($eqlogic_lamp, -10, 'once');
      return;
    }
    // couleur right
    if (in_array($_option['value'], remoteControl::cmd_color_up_array)) {
      $this->temperature_color($eqlogic_lamp, 10, 'once');
      return;
    }

    // couleur left hold
    if (in_array($_option['value'], remoteControl::cmd_color_down_hold_array)) {
      $this->temperature_color($eqlogic_lamp, -1, 'hold');
      return;
    }
    // couleur right hold
    if (in_array($_option['value'], remoteControl::cmd_color_up_hold_array)) {
      $this->temperature_color($eqlogic_lamp, 1, 'hold');
      return;
    }

    log::add(PLUGIN_NAME, 'debug', '  commande non gérée');

  }

  /**
   * Récupérer la valeur attendue spécifique lié à la commande
   */
  private function getDefinedValue($_option) {
    $cmdTrigger = $_option['event_id'];

    // remote 1
    $cmd = $this->getConfiguration('cmd_remote');
    $cmd = str_replace('#', '', $cmd);
    if ($cmd == $cmdTrigger) {
      return $this->getConfiguration('cmd_value');
    }

    // remote 2
    $cmd = $this->getConfiguration('cmd_remote2');
    $cmd = str_replace('#', '', $cmd);
    if ($cmd == $cmdTrigger) {
      return $this->getConfiguration('cmd_value2');
    }

    // remote 3
    $cmd = $this->getConfiguration('cmd_remote3');
    $cmd = str_replace('#', '', $cmd);
    if ($cmd == $cmdTrigger) {
      return $this->getConfiguration('cmd_value3');
    }

    // remote 4
    $cmd = $this->getConfiguration('cmd_remote4');
    $cmd = str_replace('#', '', $cmd);
    if ($cmd == $cmdTrigger) {
      return $this->getConfiguration('cmd_value4');
    }

    return null;
  }

  /**
   * Toggle une lampe
   */
  private function toggle($eqlogic_lamp) {
    // Recherche LIGHT_TOGGLE
    $cmd_toggle = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_TOGGLE');
    if ($cmd_toggle == null) {
      log::add(PLUGIN_NAME, 'error', 'commande LIGHT_TOGGLE non trouvée');
      throw new Exception("commande LIGHT_TOGGLE non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', '  cmd_toggle='.$cmd_toggle->getHumanName());
      
      $cmd_toggle->execCmd();
      return;
    }

    // Recherche LIGHT_STATE
    $lamp_state = $this->getStateLamp($eqlogic_lamp);

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
      log::add(PLUGIN_NAME, 'error', '   commande LIGHT_ON non trouvée');
      throw new Exception("commande LIGHT_ON non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', '   cmd_lamp_on='.$cmd_lamp_on->getHumanName());
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
      log::add(PLUGIN_NAME, 'error', '   commande LIGHT_OFF non trouvée');
      throw new Exception("commande LIGHT_OFF non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', '   cmd_lamp_off='.$cmd_lamp_off->getHumanName());
      $cmd_lamp_off->execCmd();
    }
  }

  /**
   * Obtient l'état de la lampe
   */
  private function getStateLamp($eqlogic_lamp) {
    $lamp_state = 0;
    $cmd_state = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_STATE');
    if ($cmd_state == null) {
      log::add(PLUGIN_NAME, 'error', '   commande LIGHT_STATE non trouvée');      
      throw new Exception("commande LIGHT_STATE non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', '   cmd_state='.$cmd_state->getHumanName());
      $lamp_state = $cmd_state->execCmd();
      log::add(PLUGIN_NAME, 'debug', '   lamp_state='.$lamp_state);
    }

    return $lamp_state;
  }

  /**
   * Modifier la luminosité
   */
  private function brightness($eqlogic_lamp, $step, $type) {
    log::add(PLUGIN_NAME, 'debug', '   type:'.$type);
    $brightessValue = 0;

    // Recherche la valeur de LIGHT_BRIGHTNESS
    $cmd_lamp_brightness = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_BRIGHTNESS');
    if ($cmd_lamp_brightness == null) {
      log::add(PLUGIN_NAME, 'error', '   commande LIGHT_BRIGHTNESS non trouvée');
      throw new Exception("commande LIGHT_BRIGHTNESS non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', '   cmd_lamp_brightness='.$cmd_lamp_brightness->getHumanName());
      $brightessValue = $cmd_lamp_brightness->execCmd();
      log::add(PLUGIN_NAME, 'debug', '   LIGHT_BRIGHTNESS='.$brightessValue);
    }

    // Recherche la commande LIGHT_SLIDER
    $cmd_lamp_slider = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_SLIDER');
    if ($cmd_lamp_slider == null) {
      log::add(PLUGIN_NAME, 'error', '   commande LIGHT_SLIDER non trouvée');
      throw new Exception("commande LIGHT_SLIDER non trouvée");
    } 

    // Min et Max
    $minValue = $cmd_lamp_slider->getConfiguration('minValue');
    $maxValue = $cmd_lamp_slider->getConfiguration('maxValue');

    // Telecommande
    $cmd_remote = $this->getConfiguration('cmd_remote');
    $cmd_remote = str_replace('#', '', $cmd_remote);
    $cmd_remote = cmd::byId($cmd_remote);
    if (!is_object($cmd_remote)) {
      log::add(PLUGIN_NAME, 'error', '   commande cmd_remote non trouvé');
      throw new Exception("cmd_remote non renseigné");
    }

    // PRESS
    if ($type == 'once') {
      // Action
      $command = $cmd_remote->execCmd();
      
      // Etat de la lampe
      $lamp_state = $this->getStateLamp($eqlogic_lamp);            
      if ($lamp_state == 0 && in_array($command, remoteControl::cmd_brightness_down_array)) {
        log::add(PLUGIN_NAME, 'debug', '   state eteinte');
        return;
      }


      $newBrightessValue = $brightessValue + $step;
      $newBrightessValue = max($newBrightessValue, $minValue);
      $newBrightessValue = min($newBrightessValue, $maxValue);
      log::add(PLUGIN_NAME, 'debug', '   $newBrightessValue:'.$newBrightessValue);
      
      // Lampe au maximum sans doute
      if ($brightessValue == $newBrightessValue) {
        log::add(PLUGIN_NAME, 'debug', '   commande inutile à envoyer:'.$newBrightessValue);
        return;
      }
      
      $cmd_lamp_slider->execCmd(array('slider' => $newBrightessValue, 'transition' => 300));
    }
    
    // HOLD
    if ($type == 'hold') {
      // Luminosité
      $newBrightessValue = $brightessValue;

      // Action
      $command = $cmd_remote->execCmd();
      $sign = $step <=> 0;
      while (
        in_array($command, remoteControl::cmd_brightness_down_hold_array) || 
        in_array($command, remoteControl::cmd_brightness_up_hold_array)
       )
      {
        // Etat de la lampe
        $lamp_state = $this->getStateLamp($eqlogic_lamp);
        if ($lamp_state == 0 && in_array($command, remoteControl::cmd_brightness_down_hold_array)) {
          log::add(PLUGIN_NAME, 'debug', '   state eteinte');
          break;
        }

        // Calcule nouvelle valeur
        $brightessValue = $newBrightessValue;
        $newBrightessValue = $newBrightessValue + $step;
        $newBrightessValue = max($newBrightessValue, $minValue);
        $newBrightessValue = min($newBrightessValue, $maxValue);
        log::add(PLUGIN_NAME, 'debug', '   newBrightessValue: '.$newBrightessValue);
        
        // Lampe au maximum sans doute
        if ($brightessValue == $newBrightessValue) {
          log::add(PLUGIN_NAME, 'debug', '   commande inutile à envoyer:'.$newBrightessValue);
          break;
        }

        log::add(PLUGIN_NAME, 'debug', '   commande envoyée:'.$newBrightessValue);
        $cmd_lamp_slider->execCmd(array('slider' => $newBrightessValue));//, 'transition' => 1000));        

        $step *= 2;
        if (abs($step) >= 3) {
          $step = $sign * 3;
        }
        log::add(PLUGIN_NAME, 'debug', '   step: '.$step);

        usleep(0.1 * 1000*1000);

        // Bouton appuyé
        $command = $cmd_remote->execCmd();
      }
    }
  }


    /**
   * Modifier la couleur
   */
  private function temperature_color($eqlogic_lamp, $step, $type) {
    log::add(PLUGIN_NAME, 'debug', '   type:'.$type);
    $temperatureValue = 0;

    // Recherche la valeur de LIGHT_COLOR_TEMP
    $cmd_lamp_temp_color = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_COLOR_TEMP');
    if ($cmd_lamp_temp_color == null) {
      log::add(PLUGIN_NAME, 'error', '   commande LIGHT_COLOR_TEMP non trouvée');
      throw new Exception("commande LIGHT_COLOR_TEMP non trouvée");
    } else {
      log::add(PLUGIN_NAME, 'debug', '   cmd_lamp_temp_color='.$cmd_lamp_temp_color->getHumanName());
      $temperatureValue = $cmd_lamp_temp_color->execCmd();
      log::add(PLUGIN_NAME, 'debug', '   LIGHT_COLOR_TEMP='.$temperatureValue);
    }

    // Min et Max
    $minValue = $cmd_lamp_temp_color->getConfiguration('minValue');
    $maxValue = $cmd_lamp_temp_color->getConfiguration('maxValue');

    // Recherche la commande LIGHT_SLIDER
    $cmd_lamp_slider = cmd::byEqLogicIdAndGenericType($eqlogic_lamp->getId(), 'LIGHT_SET_COLOR_TEMP');
    if ($cmd_lamp_slider == null) {
      log::add(PLUGIN_NAME, 'error', '   commande LIGHT_SET_COLOR_TEMP non trouvée');
      throw new Exception("commande LIGHT_SET_COLOR_TEMP non trouvée");
    } 

    // Telecommande
    $cmd_remote = $this->getConfiguration('cmd_remote');
    $cmd_remote = str_replace('#', '', $cmd_remote);
    $cmd_remote = cmd::byId($cmd_remote);
    if (!is_object($cmd_remote)) {
      log::add(PLUGIN_NAME, 'error', '   commande cmd_remote non trouvé');
      throw new Exception("cmd_remote non renseigné");
    }

    // PRESS
    if ($type == 'once') {
      // Etat de la lampe
      $lamp_state = $this->getStateLamp($eqlogic_lamp);            
      if ($lamp_state == 0) {
        log::add(PLUGIN_NAME, 'debug', '   state eteinte');
        return;
      }

      $newTemperatureValue = $temperatureValue + $step;
      $newTemperatureValue = max($newTemperatureValue, $minValue);
      $newTemperatureValue = min($newTemperatureValue, $maxValue);
      log::add(PLUGIN_NAME, 'debug', '   $newTemperatureValue:'.$newTemperatureValue);

      // Lampe au maximum sans doute
      if ($temperatureValue == $newTemperatureValue) {
        log::add(PLUGIN_NAME, 'debug', '   commande inutile à envoyer:'.$newTemperatureValue);
        return;
      }

      $cmd_lamp_slider->execCmd(array('slider' => $newTemperatureValue, 'transition' => 300));
    }
    
    // HOLD
    if ($type == 'hold') {
      // Température
      $newTemperatureValue = $temperatureValue;

      // Action
      $command = $cmd_remote->execCmd();
      $sign = $step <=> 0;
      while (
        in_array($command, remoteControl::cmd_color_down_hold_array) || 
        in_array($command, remoteControl::cmd_color_up_hold_array)
       )
      {
        // Etat de la lampe
        $lamp_state = $this->getStateLamp($eqlogic_lamp);
        if ($lamp_state == 0) {
          log::add(PLUGIN_NAME, 'debug', '   state eteinte');
          break;
        }

        // Calcule nouvelle valeur
        $temperatureValue = $newTemperatureValue;
        $newTemperatureValue = $newTemperatureValue + $step;
        $newTemperatureValue = max($newTemperatureValue, $minValue);
        $newTemperatureValue = min($newTemperatureValue, $maxValue);
        log::add(PLUGIN_NAME, 'debug', '   newTemperatureValue: '.$newTemperatureValue);
        
        // Lampe au maximum sans doute
        if ($temperatureValue == $newTemperatureValue) {
          log::add(PLUGIN_NAME, 'debug', '   commande inutile à envoyer:'.$newTemperatureValue);
          break;
        }

        log::add(PLUGIN_NAME, 'debug', '   commande envoyée:'.$newTemperatureValue);
        $cmd_lamp_slider->execCmd(array('slider' => $newTemperatureValue));//, 'transition' => 1000));        

        $step *= 2;
        if (abs($step) >= 3) {
          $step = $sign * 3;
        }
        log::add(PLUGIN_NAME, 'debug', '   step: '.$step);

        usleep(0.1 * 1000*1000);

        // Bouton appuyé
        $command = $cmd_remote->execCmd();
      }
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
