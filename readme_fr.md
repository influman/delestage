# Installation
D�lestage automatique d'appareils non prioritaires.  
  
Objectif :  
Lorsque la puissance globale consomm�e d�passe le seuil de d�clenchement fix�, le d�lestage coupe le premier appareil non prioritaire en �tat de marche.  
Si le seuil reste d�pass�, le d�lestage coupera l'appareil non prioritaire suivant, et ainsi de suite en Cascade.  
Si le seuil n'est plus atteint apr�s d�lestage, en mode cascado-cyclique, le d�lestage r�alisera un cycle de coupure/r�tablissement des appareils non prioritaires.  
L'int�r�t du cycle par exemple est de couper/r�tablir les diff�rents chauffages des chambres sans en p�naliser une seule trop longtemps.
Le d�lestage tentera ensuite de r�tablir progressivement les appareils pr�cedemment d�lest�s.  
  
  

### Ajout du p�riph�rique 
Cliquez sur "Configuration" / "Ajouter ou supprimer un p�riph�rique" / "Store eedomus" / "D�lestage �lectrique" / "Cr�er"  

![STEP0](https://i.imgur.com/yLrJcbK.png)


*Voici les diff�rents champs � renseigner:*

* [Obligatoire] - Le seuil de d�clenchement  
* [Obligatoire] - Le p�riph�rique compteur fournissant la puissance globale instantan�e consomm�e (en W, Va..)  
* [Obligatoire] - Les 3 p�riph�riques non prioritaires (chauffages...) � d�lester  
* [Obligatoire] - Les 3 valeurs associ�es aux p�riph�riques permettant de les couper  
  
Vous obtenez alors un capteur "STATUT" qui fournit l'�tat des appareils non prioritaires s�lectionn�s, ainsi qu'un actionneur pour d�finir le mode de fonctionnement du d�lesteur : Arr�t / Cascade / Cascado-Cyclique.  
  
![STEP1](https://i.imgur.com/97qvdnZ.png)

### Param�trage
  
Apr�s installation, lancez l'action "MaJ appareils" de l'actionneur "Mode", cela permet de fournir au d�lesteur les diff�rents appareils.  
Vous pouvez ajouter/supprimer des appareils non prioritaires � la liste post-installation, en modifiant le param�tre [VAR3].  
Respectez alors le formaliste suivant : 123456(0),456789(50),...  
O� 123456 et 456789 sont les codes API des p�riph�riques prioritaires, avec entre parenth�ses leur valeur d'arr�t respective.  
  
S�lectionnez le mode Cascado-Cyclique s'il s'agit de radiateurs, sinon le mode Cascade sera plus ad�quat.  
  
![STEP2](https://i.imgur.com/ilLJbEO.png)
  
      
Influman 2018  
therealinfluman@gmail.com  
[Paypal Me](https://www.paypal.me/influman "paypal.me")  