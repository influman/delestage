<?php  
    $xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>";  
	//**********************************************************************************************************
    // V1.0 : Script de gestion de délestage électrique
	//*************************************** ******************************************************************
    // recuperation des infos depuis la requete
	// PUISSANCE, COMPTEUR - VAR1
    $power = getArg("power", true);
	// Périphériques non prioritaires
	$names = getArg("names", true);
    $periphs = getArg("periph", true);
	// actions
	$action = getArg("action", true);
	// mode
	$mode = getArg("value", false, '0'); // 0 - Arrêt, 1 - Cascade, 2 - Cascado-cyclique
	// API DU PERIPHERIQUE APPELANT LE SCRIPT
    $api_script = getArg('eedomus_controller_module_id'); 
	
	$current_mode = "";
	$tab_devices_api = array();
	$basetempo = 12;
	$tab_param = explode(",",$power);
	$puissancemax = $tab_param[0];
	$api_compteur = $tab_param[1];
	
	// CHARGEMENT
	$preload = loadVariable("DELESTAGE_APIMODE");
	if ($preload != '' && substr($preload, 0, 8) != "## ERROR") {
		$api_mode = $preload;
	} else {
		$api_mode = 0;
	}
	
	$preload = loadVariable("DELESTAGE_APISTATUS");
	if ($preload != '' && substr($preload, 0, 8) != "## ERROR") {
		$api_status = $preload;
	} else {
		$api_status = 0;
	}
	
	$preload = loadVariable("DELESTAGE_TEMPO");
	if ($preload != '' && substr($preload, 0, 8) != "## ERROR") {
		$tempo = $preload;
	} else {
		$tempo = $basetempo;
	}
	
	$preload = loadVariable("DELESTAGE_MODE");
	if ($preload != '' && substr($preload, 0, 8) != "## ERROR") {
		$current_mode = $preload;
		$mode_ok = true;
	} else {
		$mode_ok = false;
	}
	
	$preload = loadVariable("DELESTAGE_API");
	if ($preload != '' && substr($preload, 0, 8) != "## ERROR") {
		$tab_devices_api = $preload;
		$tab_devices_off = loadVariable("DELESTAGE_OFF");
		$tab_devices_names = loadVariable("DELESTAGE_NAME");
		$tab_devices_save = loadVariable("DELESTAGE_SAVE");
		$tab_devices_dlst = loadVariable("DELESTAGE_DLST");
		$devices_ok = true;
	} else {
		$devices_ok = false;
	}
	// ****************************************************************	
	// ACTION SET : ARRET | CASCADE | CASCADO-CYCLIQUE | MàJ Appareils | Délestage demandé
	if ($action == "set") {
		// Arrêt du délesteur
		if ($mode == "0") {
			saveVariable("DELESTAGE_MODE", $mode);
			if ($devices_ok) { // Vidage des données de délestage le cas échéant, retour à la valeur initiale
				for($i = 1;$i <= count($tab_devices_api);$i++){
					if ($tab_devices_dlst[$i] == true) {
						setValue($tab_devices_api[$i], $tab_devices_save[$i]); 
						$tab_devices_dlst[$i] = false;
						saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
					}
				}
				$tempo = $basetempo;
				saveVariable("DELESTAGE_TEMPO", $tempo);
			}
			die();
		}
		// Mode Cascade ou Cascado-cyclique
		if ($mode == "1" || $mode == "2") {
			saveVariable("DELESTAGE_MODE", $mode);
			if (!$devices_ok) {
				$mode = "99";
			}
		}
		// Mise à jour de la liste des appaareils - "99"
		if ($mode == "99") { 
			if ($devices_ok) { // Vidage des données de délestage le cas échéant, retour à la valeur initiale
				for($i = 1;$i <= count($tab_devices_api);$i++){
					if ($tab_devices_dlst[$i] == true) {
						setValue($tab_devices_api[$i], $tab_devices_save[$i]); 
						$tab_devices_dlst[$i] = false;
					}
				}
				$tempo = $basetempo;
				saveVariable("DELESTAGE_TEMPO", $tempo);
			}
			// mise à jour liste des appareils non prioritaires
			saveVariable("DELESTAGE_APIMODE", $api_script);
			$periphs_lu = explode(",",$periphs);
			$names_lu = explode(",",$names);
			$tab_devices_api = array();
			$tab_devices_names = array();
			$tab_devices_off = array();
			$tab_devices_save = array();
			$tab_devices_dlst = array();
			$idevice = 1;
			if ($periphs_lu[0] != "") {
				$tab_periphs_split = explode("-",$periphs_lu[0]);
				$tab_devices_api[$idevice] = $tab_periphs_split[0];
				$tab_devices_off[$idevice] = utf8_decode($tab_periphs_split[1]); // param recodé en ISO 8859-1
				if (array_key_exists(0,$names_lu)) {
					$tab_devices_names[$idevice] = $names_lu[0];
				} else  {
					$tab_devices_names[$idevice] = "";
				}
				$tab_devices_dlst[$idevice] = false;
				$device = getValue($tab_devices_api[$idevice]);
				$tab_devices_save[$idevice] = $device['value'];
				while(count($periphs_lu) > $idevice) {
					$idevice++;
					if (strpos($periphs_lu[$idevice - 1], "plugin") !== false) {
						break;
					} else {
						$tab_periphs_split = explode("-",$periphs_lu[$idevice - 1]);
						$tab_devices_api[$idevice] = $tab_periphs_split[0];
						$tab_devices_off[$idevice] = utf8_decode($tab_periphs_split[1]); // param recodé en ISO 8859-1
						$key = $idevice - 1;
						if (array_key_exists($key,$names_lu)) {
							$tab_devices_names[$idevice] = $names_lu[$key];
						} else {
							$tab_devices_names[$idevice] = "";
						}
						$tab_devices_dlst[$idevice] = false;
						$device = getValue($tab_devices_api[$idevice]);
						$tab_devices_save[$idevice] = $device['value'];
					}
				}
			}
			saveVariable("DELESTAGE_API", $tab_devices_api);
			saveVariable("DELESTAGE_NAME", $tab_devices_names);
			saveVariable("DELESTAGE_OFF", $tab_devices_off);
			saveVariable("DELESTAGE_SAVE", $tab_devices_save);
			saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
			// retour au mode précédent
			if ($mode_ok) {
				setValue($api_script, $current_mode);
			} else {
				setValue($api_script, 0);
				saveVariable("DELESTAGE_MODE", "0");
			}
			die();
		}	
		// DELESTAGE DEMANDE SUR REGLE DE DETECTION DEPASSEMENT DE SEUIL - "999"
		if ($mode == "999") { 
			if ($mode_ok && $current_mode != 0 && $devices_ok) { // PAS EN MODE ARRET (normalement la règle le prévoit)
				// ON DEPASSE LE SEUIL DONC ON ETEINT LE PROCHAIN (même en cyclique)
				// SI TOUT EST ETEINT DEJA, ET BIEN TANTPIS..
				$delestok = false;
				$index_current_dlst = "";
				$dlst_value = "";
				for($i = 1;$i <= count($tab_devices_api);$i++){
					$device = getValue($tab_devices_api[$i]);
					$device_val = $device['value'];
					if ($tab_devices_dlst[$i] == false && $device_val != $tab_devices_off[$i]) { // 1er periph non délesté et pas déjà sur valeur d'arrêt
						$tab_devices_save[$i] = $device_val; // sauvegarde de la valeur du périphérique avant délestage
						setValue($tab_devices_api[$i], $tab_devices_off[$i]); // mise à valeur d'arrêt du périphérique
						$tab_devices_dlst[$i] = true; // enregistrement du délestage pour ce périphérique
						$index_current_dlst = $i;
						$delestok = true;
						$dlst_value = $tab_devices_off[$i];
						saveVariable("DELESTAGE_SAVE", $tab_devices_save);
						saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
						break;
					}
				}
				$tempo = $basetempo; // RAZ du TEMPO d'ARRET
				saveVariable("DELESTAGE_TEMPO", $tempo);
				// Affichage instantané du délestage réalisé dans le Statut
				if ($delestok) {
					$statut = "";
					$device_text = $dlst_value;
					$device_name = $tab_devices_names[$index_current_dlst];
					// recherche de la description associée à la valeur de délestage
					$device_tab_valuelist = getPeriphValueList($tab_devices_api[$index_current_dlst]);
					foreach($device_tab_valuelist As $device_tab_value) {
						if ($device_tab_value["value"] == $dlst_value) {
							$device_text = $device_tab_value['state'];
							break;
						}
					}
					if ($device_name != "") {
						$statut .= "Délestage ".$device_name.": ".$device_text;
					} else {
						$statut .= "Délestage ".$device_text;
					}
					setValue($api_status, $statut, false, true);
				}
			}
			
		}
    }
	
	if ($action == "status") { // POLLING DU DELESTEUR
		saveVariable("DELESTAGE_APISTATUS", $api_script);
		$xml .= "<DELESTAGE><STATUT>";
		$statut = "Aucun appareil sélectionné...";
		if ($devices_ok && $mode_ok && $current_mode !=0) {
			$change1ok = false;
			$index_current_change1 = "";
			$changevalue1 = "";
			$change2ok = false;
			$index_current_change2 = "";
			$changevalue2 = "";
			// Gestion du délestage actuel (et cycle le cas échéant)
			$cpt = getValue($api_compteur);
			$valeur_compteur = $cpt['value'];
			
			if ($valeur_compteur >= $puissancemax) { // on est au dessus du seuil encore, on déleste le suivant
				for($i = 1;$i <= count($tab_devices_api);$i++){
					$device = getValue($tab_devices_api[$i]);
					$device_val = $device['value'];
					if ($tab_devices_dlst[$i] == false && $device_val != $tab_devices_off[$i]) { // periph non délesté et pas déjà sur off
						$tab_devices_save[$i] = $device_val; // sauvegarde de la valeur du périphérique avant délestage
						setValue($tab_devices_api[$i], $tab_devices_off[$i]); // extinction du périphérique
						$tab_devices_dlst[$i] = true; // enregistrement du délestage
						$index_current_change1 = $i;
						$change1ok = true;
						$changevalue1 = $tab_devices_off[$i];
						saveVariable("DELESTAGE_SAVE", $tab_devices_save);
						saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
						
						break;
					}
				}
				$tempo = $basetempo;
				saveVariable("DELESTAGE_TEMPO", $tempo);
			} else { 
				// On est en dessous du seuil
				// recherche si un périph délesté en cours
				$premier_dlst = 0;
				$nbdelest = 0;
				for($i = 1;$i <= count($tab_devices_api);$i++){
					if ($tab_devices_dlst[$i]) { // un périph délesté
						if ($premier_dlst == 0) {
							$premier_dlst = $i;
						}
						$nbdelest++;
					}
				}	
				if ($nbdelest > 0) { // un périph est déjà délesté
					$tempo = $tempo - 1; // on passe un tempo en dessous du seuil
					saveVariable("DELESTAGE_TEMPO", $tempo);
					if ($tempo == 0) { // on arrête le 1er délestage
						$tempo = $basetempo;
						if ($nbdelest > 1) {
							$tempo = $basetempo / 2;
							
						}
						saveVariable("DELESTAGE_TEMPO", $tempo);
						// retour à la normale du premier delesté
						setValue($tab_devices_api[$premier_dlst], $tab_devices_save[$premier_dlst]); // retour à sa valeur initiale
						$tab_devices_dlst[$premier_dlst] = false; // fin du délestage du premier
						$index_current_change1 = $premier_dlst;
						$change1ok = true;
						$changevalue1 = $tab_devices_save[$premier_dlst];
						saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
						if ($nbdelest == 1) {
							setValue($api_mode, $current_mode);
						}
					} else { // On garde l'état actuel
						if ($current_mode == 2) {// mode cyclique
							// recherche du prochain périph à délester dans le cycle
							$prochain_dlst = 0;
							for($i = $premier_dlst + 1;$i <= count($tab_devices_api);$i++){
								$device = getValue($tab_devices_api[$i]);
								$device_val = $device['value'];
								if ($tab_devices_dlst[$i] == false && $device_val != $tab_devices_off[$i]) { // un périph non délesté et non off
									$prochain_dlst = $i;
									break;
								}
							}
							if ($prochain_dlst == 0) {
								for($i = 1;$i < $premier_dlst;$i++){
									$device = getValue($tab_devices_api[$i]);
									$device_val = $device['value'];
									if ($tab_devices_dlst[$i] == false && $device_val != $tab_devices_off[$i]) { // un périph non délesté et non off
										$prochain_dlst = $i;
										break;
									}
								}
							}
							if ($prochain_dlst > 0) { // prochain DLST trouvé, on échange
								$tab_devices_save[$prochain_dlst] = $device_val; // sauvegarde de la valeur du périphérique avant délestage
								setValue($tab_devices_api[$prochain_dlst], $tab_devices_off[$prochain_dlst]); // extinction du périphérique
								$index_current_change2 = $prochain_dlst;
								$change2ok = true;
								$changevalue2 = $tab_devices_off[$prochain_dlst];
								$tab_devices_dlst[$prochain_dlst] = true; // enregistrement du délestage
								setValue($tab_devices_api[$premier_dlst], $tab_devices_save[$premier_dlst]); // retour à sa valeur du 1er périphérique
								$tab_devices_dlst[$premier_dlst] = false; // fin du délestage du premier
								$index_current_change1 = $premier_dlst;
								$change1ok = true;
								$changevalue1 = $tab_devices_save[$premier_dlst];
								saveVariable("DELESTAGE_SAVE", $tab_devices_save);
								saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
							}
						}
					}
				}
			}
		}
		sleep(5);
		if ($devices_ok) {
			$statut = "";
			// Affichage statut
			for($i = 1;$i <= count($tab_devices_api);$i++){
				if ($i>1) {
					$statut .= " | ";
				}
				$device_name = $tab_devices_names[$i];
				$device_text = "";
				
				if ($i == $index_current_change1 && $change1ok) { //ce périphérique vient de changer
					$device_tab_valuelist = getPeriphValueList($tab_devices_api[$i]);
					foreach($device_tab_valuelist As $device_tab_value) {
						if ($device_tab_value["value"] == $changevalue1) {
							$device_text = $device_tab_value['state'];
							break;
						}
					}
				} 
				if ($i == $index_current_change2 && $change2ok) {
					$device_tab_valuelist = getPeriphValueList($tab_devices_api[$i]);
					foreach($device_tab_valuelist As $device_tab_value) {
						if ($device_tab_value["value"] == $changevalue2) {
							$device_text = $device_tab_value['state'];
							break;
						}
					}
				} 
				if ($device_text == "") {
					$device = getValue($tab_devices_api[$i], true);
					$device_text =  $device['value_text'];
					$device_value = $device['value'];
					if ($device_text == "") {
						$device_text = $device_value;
					}
				}
				if ($device_name != "") {
					$statut .= $device_name.": ".$device_text;
				} else {
					$statut .= $device_text;
				}
				if ($tab_devices_dlst[$i] == true) {
					$statut .= " (D)";
				}
			}
		}
		
		$xml .= $statut;
		$xml .= "</STATUT>";
		$xml .= "</DELESTAGE>";
		sdk_header('text/xml');
		echo $xml;
	}
?>
