# Installation
Délestage automatique d'appareils non prioritaires.  
  
Objectif :  
Lorsque la puissance globale consommée dépasse le seuil de déclenchement fixé, le délestage coupe le premier appareil non prioritaire en état de marche.  
Si le seuil reste dépassé, le délestage coupera l'appareil non prioritaire suivant, et ainsi de suite en Cascade.  
Si le seuil n'est plus atteint après délestage, en mode cascado-cyclique, le délestage réalisera un cycle de coupure/rétablissement des appareils non prioritaires.  
L'intérêt du cycle par exemple est de couper/rétablir les différents chauffages des chambres sans en pénaliser une seule trop longtemps.
Le délestage tentera ensuite de rétablir progressivement les appareils précedemment délestés.  
  
  

### Ajout du périphérique 
Cliquez sur "Configuration" / "Ajouter ou supprimer un périphérique" / "Store eedomus" / "Délestage électrique" / "Créer"  

![STEP0](https://i.imgur.com/yLrJcbK.png)


*Voici les différents champs à renseigner:*

* [Obligatoire] - Le seuil de déclenchement  
* [Obligatoire] - Le périphérique compteur fournissant la puissance globale instantanée consommée (en W, Va..)  
* [Obligatoire] - Les 3 périphériques non prioritaires (chauffages...) à délester  
* [Obligatoire] - Les 3 valeurs associées aux périphériques permettant de les couper  
  
Vous obtenez alors un capteur "STATUT" qui fournit l'état des appareils non prioritaires sélectionnés, ainsi qu'un actionneur pour définir le mode de fonctionnement du délesteur : Arrêt / Cascade / Cascado-Cyclique.  
  
![STEP1](https://i.imgur.com/97qvdnZ.png)

### Paramétrage
  
Après installation, lancez l'action "MaJ appareils" de l'actionneur "Mode", cela permet de fournir au délesteur les différents appareils.  
Vous pouvez ajouter/supprimer des appareils non prioritaires à la liste post-installation, en modifiant le paramètre [VAR3].  
Respectez alors le formaliste suivant : 123456(0),456789(50),...  
Où 123456 et 456789 sont les codes API des périphériques prioritaires, avec entre parenthèses leur valeur d'arrêt respective.  
  
Sélectionnez le mode Cascado-Cyclique s'il s'agit de radiateurs, sinon le mode Cascade sera plus adéquat.  
  
![STEP2](https://i.imgur.com/ilLJbEO.png)
  
      
Influman 2018  
therealinfluman@gmail.com  
[Paypal Me](https://www.paypal.me/influman "paypal.me")  