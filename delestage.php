<?php  
    $xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>";  
	//**********************************************************************************************************
    // V0.1 : Script de gestion de délestage électrique
	//http://localhost/script/?exec=delestage.php&power=[VAR1]&apii=[VAR2]&periph=[VAR3]&action=status|set&value=0|1|2|99|999
    //*************************************** ******************************************************************
    // recuperation des infos depuis la requete
	// PUISSANCE - VAR1
    $puissancemax = getArg("power", $mandatory = true);
	// API COMPTEUR - VAR2
    $api_compteur = getArg("apii", $mandatory = true);
    // Périphériques non prioritaires
    $periphs = getArg("periph", $mandatory = true);
	// actions
	$action = getArg("action", $mandatory = true);
	// mode
	$mode = getArg("value", $mandatory = false, $default = '0'); // 0 - Arrêt, 1 - Cascade, 2 - Cascado-cyclique
	// API DU PERIPHERIQUE APPELANT LE SCRIPT
    $api_script = getArg('eedomus_controller_module_id'); 
	
	$current_mode = "";
	$tab_devices_api = array();
	$basetempo = 12;
	
	// CHARGEMENT
	$preload = loadVariable("DELESTAGE_APIMODE");
	if ($preload != '' && substr($preload, 0, 8) != "## ERROR") {
		$api_mode = $preload;
	} else {
		$api_mode = 0;
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
		$tab_devices_save = loadVariable("DELESTAGE_SAVE");
		$tab_devices_dlst = loadVariable("DELESTAGE_DLST");
		$devices_ok = true;
	} else {
		$devices_ok = false;
	}
		
	// ACTION SET
	if ($action == "set") {
		if ($mode == "0") {
			// Arrêt
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
		if ($mode == "1" || $mode == "2") {
			// Cascade ou Cascado-cyclique
			saveVariable("DELESTAGE_MODE", $mode);
			if (!$devices_ok) {
				$mode = "99";
			}
		}
		
		if ($mode == "99") { 
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
			// mise à jour liste des appareils non prioritaires
			saveVariable("DELESTAGE_APIMODE", $api_script);
			$periphs_lu = explode(",",$periphs);
			$tab_devices_api = array();
			$tab_devices_off = array();
			$tab_devices_save = array();
			$tab_devices_dlst = array();
			$idevice = 1;
			if ($periphs_lu[0] != "") {
				list($tab_devices_api[$idevice], $tab_devices_off[$idevice]) = sscanf($periphs_lu[0], "%d(%d)");
				$tab_devices_dlst[$idevice] = false;
				$device = getValue($tab_devices_api[$idevice]);
				$tab_devices_save[$idevice] = $device['value'];
				while(count($periphs_lu) > $idevice) {
					$idevice++;
					if (strpos($periphs_lu[$idevice - 1], "plugin") !== false) {
						break;
					} else {
						list($tab_devices_api[$idevice], $tab_devices_off[$idevice]) = sscanf($periphs_lu[$idevice - 1], "%d(%d)");
						$tab_devices_dlst[$idevice] = false;
						$device = getValue($tab_devices_api[$idevice]);
						$tab_devices_save[$idevice] = $device['value'];
					}
				}
			}
			saveVariable("DELESTAGE_API", $tab_devices_api);
			saveVariable("DELESTAGE_OFF", $tab_devices_off);
			saveVariable("DELESTAGE_SAVE", $tab_devices_save);
			saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
			// retour au mode
			if ($mode_ok) {
				setValue($api_script, $current_mode, $verify_value_list = false, $update_only= true);
			} else {
				setValue($api_script, "0");
				saveVariable("DELESTAGE_MODE", "0");
			}
			die();
		}	
		
		if ($mode == "999") { // DELESTAGE SUR REGLE DE DETECTION DEPASSEMENT DE SEUIL
			if ($mode_ok && $current_mode != 0 && $devices_ok) { // PAS EN MODE ARRET (normalement la règle le prévoit)
				// ON DEPASSE LE SEUIL DONC ON ETEINT LE PROCHAIN (même en cyclique)
				// SI TOUT EST ETEINT DEJA, ET BIEN TANTPIS..
				for($i = 1;$i <= count($tab_devices_api);$i++){
					$device = getValue($tab_devices_api[$i]);
					$device_val = $device['value'];
					if ($tab_devices_dlst[$i] == false && $device_val != $tab_devices_off[$i]) { // 1er periph non délesté et pas déjà sur off
						$tab_devices_save[$i] = $device_val; // sauvegarde de la valeur du périphérique avant délestage
						setValue($tab_devices_api[$i], $tab_devices_off[$i]); // extinction du périphérique
						$tab_devices_dlst[$i] = true; // enregistrement du délestage
						saveVariable("DELESTAGE_SAVE", $tab_devices_save);
						saveVariable("DELESTAGE_DLST", $tab_devices_dlst);
						break;
					}
				}
				$tempo = $basetempo; // RAZ du TEMPO d'ARRET
				saveVariable("DELESTAGE_TEMPO", $tempo);
			}
			
			
		}
    }
	
	if ($action == "status") { // POLLING DU DELESTEUR
		
		$xml .= "<DELESTAGE><STATUT>";
		$statut = "Aucun appareil sélectionné...";
		if ($devices_ok && $mode_ok && $current_mode !=0) {
			
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
								$tab_devices_dlst[$prochain_dlst] = true; // enregistrement du délestage
								setValue($tab_devices_api[$premier_dlst], $tab_devices_save[$premier_dlst]); // retour à sa valeur du 1er périphérique
								$tab_devices_dlst[$premier_dlst] = false; // fin du délestage du premier
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
				$device = getValue($tab_devices_api[$i], true);
				$device_text = $device['value_text'];
				$device_value = $device['value'];
				if ($device_text == "") {
					$device_text = $device_value;
				}
				$statut .= $device_text;
				if ($tab_devices_dlst[$i] == true) {
					$statut .= " (DLST)";
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
