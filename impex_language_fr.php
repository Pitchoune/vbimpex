<?php
/*======================================================================*\
|| ####################################################################
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # http://www.vbulletin.com
|| ####################################################################
\*======================================================================*/
error_reporting(E_ALL & ~E_NOTICE);

if (!defined('IDIR')) { die; }

#####################################
# ImpEx text, not really phrases
#####################################

# Phrased & Error logging
# phpBB2
# ipb2
# eve
# photopost-vBulletin user table
# vbzoom
# discuz2
# phpwind
# ipb1.3
# smf
# ubb_threads
# snitz
# vb2
# vb3

#####################################
# index.php phrases
#####################################

$impex_phrases['enter_customer_number'] 					= 'Veuillez insérer le code d\'accès';

$impex_phrases['using_local_config']						= 'Utilisation du fichier includes/config.php comme configuration cible.';
$impex_phrases['using_impex_config']						= 'Utilisation du fichier ImpExConfig.php comme configuration cible.';
$impex_phrases['no_mssql_support']							= '<br /><br />Vous n\'avez pas le support MSSQL dans cette version de PHP, le système ne pourra pas se connecter à la base de données source.';
$impex_phrases['no_mssql_support_link']						= '<br />Veuillez consulter cette page : <a target="_blank" href="http://www.php.net/manual/fr/book.mssql.php">PHP MSSQL</a>';
$impex_phrases['no_source_set']								= 'Aucune base de données source a été renseignée dans le fichier ImpExConfig.php';
$impex_phrases['source_not_exist']							= 'La base de données source dans le fichier ImpExConfig.php n\'existe pas.';
$impex_phrases['no_source_connection_check_login']			= 'La base de données source n\'a pas été sélectionnée. Êtes-vous certain des informations de connexion à la base de données source ?';
$impex_phrases['sourceexists_is_false'] 					= 'Vous avez défini la variable sourceexists sur FALSE dans le fichier ImpExConfig.php, ceci signifie que vous ne souhaitez pas importer depuis une base de données, ce système utilise une base de données comme source.';
$impex_phrases['failed_connection']							= 'La connexion au serveur source a échoué. Veuillez vérifier l\'identifiant et le mot de passe associé.';
$impex_phrases['db_cleanup']								= 'Nettoyage de la base de données &amp; redémarrage';
$impex_phrases['online_manual']								= 'Manuel en ligne (anglais)';
$impex_phrases['online_manual_url']							= 'http://www.vbulletin.com/docs/html/impex';
$impex_phrases['cleanup_module_title']						= 'Nettoyer le module';
$impex_phrases['feedback_module_title']						= 'Avis';
$impex_phrases['build_version']								= 'ImpEx version : ';
$impex_phrases['remove']									= 'Supprimez ImpEx une fois votre importation complète et finale !';
$impex_phrases['finished_import']							= 'Une fois que vous avez fini d\'utiliser ces modules, suivez les instructions suivantes pour terminer l\'importation (anglais)';
$impex_phrases['finished_import_url']						= 'http://www.vbulletin.com/docs/html/impex_cleanup';

#####################################
# help.php phrases
#####################################

$impex_phrases['help_page']									= 'Aide ImpEx';
$impex_phrases['action_1']									= '<p>Les liens suivants vous permettront de recommencer une importation, supprimer la session actuelle ou de supprimer les identifiants d\'importations pour effectuer une nouvelle importation consécutive.</p>';

$impex_phrases['action_2_title']							= 'Annuler';
$impex_phrases['action_2_desc']								= '<a href="index.php">Pour effectuer aucune action et revenir à l\'importation, cliquez ici.</a>';

$impex_phrases['action_3_title']							= 'Supprimer la session';
$impex_phrases['action_3_desc']								= '<a href="help.php?action=delsess">Pour supprimer la session actuelle et continuer l\'importation, cliquez ici.</a>';

$impex_phrases['delete_session_and_data']					= '<strong>Suppression de la session et de toutes les données <em>importées</em></strong>';

$impex_phrases['action_4']									= 'Données du Forum - <a href="help.php?action=delall">Pour supprimer la session d\'importation actuelle ainsi que les données importées pour une nouvelle tentative propre, cliquez ici.</a>';
$impex_phrases['action_7']									= 'Données du Cms - <a href="help.php?action=delall&amp;type=cms">Pour supprimer la session d\'importation actuelle ainsi que les données importées pour une nouvelle tentative propre, cliquez ici.</a>';
$impex_phrases['action_9']									= 'Données du Blog - <a href="help.php?action=delall&amp;type=blog">Pour supprimer la session d\'importation actuelle ainsi que les données importées pour une nouvelle tentative propre, cliquez ici.</a>';

$impex_phrases['action_5']									= 'Identifiants du Forum - <a href="help.php?action=delids">Pour supprimer les identifiants d\'importation de la base de données ainsi que la session actuelle, cliquez ici. Ceci vous permettra d\'effectuer plusieurs importations consécutives.</a>';
$impex_phrases['action_8']									= 'Identifiants du Cms - <a href="help.php?action=delids&amp;type=cms">Pour supprimer les identifiants d\'importation de la base de données ainsi que la session actuelle, cliquez ici. Ceci vous permettra d\'effectuer plusieurs importations consécutives.</a>';
$impex_phrases['action_10']									= 'Identifiants du Blog - <a href="help.php?action=delids&amp;type=blog">Pour supprimer les identifiants d\'importation de la base de données ainsi que la session actuelle, cliquez ici. Ceci vous permettra d\'effectuer plusieurs importations consécutives.</a>';

$impex_phrases['action_6_title']							= '<strong>Supprimer les forums / discussions / messages en double</strong>';
$impex_phrases['action_6_desc']								= '<a href="help.php?action=deldupe">Ceci va supprimer toute donnée qui a un identifiant d\'importation en double. À utiliser avec une EXTRÊME précaution, les résultats peuvent varier selon les sources. <b>EFFECTUEZ UNE SAUVEGARDE DE VOTRE BASE DE DONNÉES AVANT D\'UTILISER CECI !</b></a>';


$impex_phrases['dell_session_1']							= '<b>Suppression de la session ImpEx.</b>';
$impex_phrases['dell_session_2']							= '<p>Après avoir exécuté ceci, les données déjà importées seront laissés dans la base de données. Il est recommandé d\'exécuter de nouveau deux fois chaque module déjà terminé pour vous assurer que les données ont bien été nettoyéees.</p>';
$impex_phrases['dell_session_3']							= '<p>Par exemple, si l\'importation a mis trop de temps sur l\'importation des utilisateurs et que vous êtes venus ici pour supprimer la session, une fois effectué, exécutez le module correspondant <b>deux fois</b>, ceci vous permettra d\'être certain qu\'à sa seconde exécution, <b>toutes</b> les données importées précédemment seront supprimées.</p>';
$impex_phrases['dell_session_4']							= '<p>Ceci peut arriver avec tous les modules, les exécuter plus d\'une fois nettoiera les données précédentes qui ont un identifiant d\'importation.</p>';
$impex_phrases['dell_session_5']							= '<p><b>Session supprimée avec succès !</b></p>';
$impex_phrases['dell_session_6']							= '<p><a href="index.php">Cliquez ici pour retourner sur la page d\'importation.</a></p>';


$impex_phrases['deleting_session']							= '<b>Suppression de la session ImpEx</b>';
$impex_phrases['session_deleted']							= '<p><b>Session supprimée avec succès !</b></p>';
$impex_phrases['deleting_from']								= 'Suppression des données importées depuis ';


$impex_phrases['deleting_duplicates']						= '<b>Suppression des doublons</b>';
$impex_phrases['refresh_deletion_if_needed']				= 'Tant que les compteurs ne sont pas tous à 0, vous pouvez rafraîchir la page.';
$impex_phrases['click_to_return']							= '<p><a href="index.php">Cliquez ici pour retourner sur la page d\'importation.</a></p>';


$impex_phrases['remove_importids']							= '<strong>Supprimer les identifiants d\'importation</strong>';

$impex_phrases['del_ids_1']									= 'Paramètre';
$impex_phrases['del_ids_2']									= 'dans la table';
$impex_phrases['del_ids_3']									= 'à 0....';

$impex_phrases['help_error']            					= 'Erreur';
$impex_phrases['cant_read_config']							= 'ImpEx n\'arrive pas à lire les détails de la base de données cible depuis le fichier impex/ImpExConfig.php OU ../includes/config.php.<br /> Veuillez insérer les données de connexion à la base de données cible dans le fichier ImpExConfig.php ou utilisez ImpEx intégré dans vBulletin.';
$impex_phrases['cant_find_config']      					= 'Impossible de trouver le fichier ImpExConfig.php';

#####################################
# ImpExDisplay.php phrases
#####################################

$impex_phrases['system']									= 'Système';
$impex_phrases['version']									= 'Version';

$impex_phrases['title']										= 'Importer / Exporter';
$impex_phrases['redo']										= 'Recommencer le module';
$impex_phrases['start_module']								= 'Démarrer le module';
$impex_phrases['minute_title']								= ' min(s)'; # Note space
$impex_phrases['seconds_title']								= ' sec(s)'; # Note space
$impex_phrases['totals']									= 'Totaux :';

$impex_phrases['select_system']								= 'Sélectionnez le système :: ';
$impex_phrases['select_target_system']						= 'Sélectionnez la version <b>et</b> le produit à exporter vers :: ';

$impex_phrases['installed_systems']							= 'Importeurs installés';
$impex_phrases['start_import']								= 'Commencer l\'importation';

$impex_phrases['module']									= 'Module';
$impex_phrases['action']									= 'Action';
$impex_phrases['completed']									= 'Terminé';

$impex_phrases['second']									= 'seconde';  # lowercase
$impex_phrases['seconds']									= 'secondes'; # lowercase

$impex_phrases['successful']								= 'Succès';
$impex_phrases['failed']									= 'Échec';
$impex_phrases['redirecting']								= 'Redirection en cours...';
$impex_phrases['timetaken']									= 'Temps d\'utilisation';

$impex_phrases['associate']									= 'Associer';
$impex_phrases['quit']										= 'Quitter';

$impex_phrases['submit']									= 'Envoyer';
$impex_phrases['reset']										= 'Réinitialiser';

$impex_phrases['yes']										= 'Oui';
$impex_phrases['no']										= 'Non';
$impex_phrases['userset_nohtml']							= 'Défini par l\'utilisateur (pas de HTML)';

$impex_phrases['mins']										= ' min(s)';
$impex_phrases['secs']										= ' sec(s)';

#####################################
# ImpExDatabaseCore.php phrases
#####################################

$impex_phrases['sourceexists_true']     					= '<h4>Veuillez définir \'$impexconfig[\'sourceexists\'] = true\' dans le fichier ImpExConfig.php.</h4>';
$impex_phrases['validtable_overridden'] 					= '<h4>ImpExDatabase::check_database - $this->_valid_tables doit être écrasé dans le module 000 du système.</h4>';
$impex_phrases['testing_source_against']					= 'Test de la source depuis : ';
$impex_phrases['file_missing_empty_hidden']					= 'Fichier {1} est manquant, vide ou caché.';
$impex_phrases['save_file_failed']							= 'La commande de création / sauvegarde du fichier a échoué. Veuillez vérifier la localisation du répertoire cible ainsi que ses permissions.';
$impex_phrases['halted_missing_fields_db']					= 'ImpEx ne peut pas continuer et a arrêté suite à des champs manquants dans la base de données source :';
$impex_phrases['repair_source_db']							= 'Veuillez réparer la base de données source et recommencer l\'importation.';
$impex_phrases['file_already_exists_select_target']			= 'Le fichier {1} existe déjà. Veuillez sélectionner un répertoire cible avec aucun fichier comportant des noms à importer.';

#####################################
# ImpExFunction.php phrases
#####################################
$impex_phrases['ok']										= 'OK';
$impex_phrases['not_ok']									= 'PAS OK';
$impex_phrases['path_is_incorrect']							= ' n\'est pas correct(e).';
$impex_phrases['check_board_structure']						= 'Veuillez vérifier la structure des fichiers du forum.';

#####################################
# ImpExModule.php phrases
#####################################
$impex_phrases['module_cant_load_class']					= 'ImpExModule::init a échoué à trouver le fichier « {1} »';
$impex_phrases['module_check_readable_files']				= 'Veuillez vérifier le chemin et que le fichier soit accessible par le serveur web.';

#####################################
# ImpExSession.php phrases
#####################################
$impex_phrases['timestamp']									= 'Horodatage';
$impex_phrases['type']										= 'Type';
$impex_phrases['module']									= 'Module';
$impex_phrases['errorstring']								= 'Chaîne d\'erreur';
$impex_phrases['remedy']									= 'Remède';
$impex_phrases['errorcount']								= 'Nombre d\'erreurs :';

#####################################
# Import common
#####################################

$impex_phrases['continue']									= 'Continuer';
$impex_phrases['reset']										= 'Réinitialiser';
$impex_phrases['importing']									= 'Importation de';
$impex_phrases['import']									= 'Importer';
$impex_phrases['imported']									= 'Importé';
$impex_phrases['imported_qty']								= 'Importé :';
$impex_phrases['from']										= 'De';
$impex_phrases['to']										= 'À'; # i.e. Importing 300 posts From 500 To 800
$impex_phrases['dependency_error']							= 'Erreur de dépendance';
$impex_phrases['dependant_on']								= 'Ce module est dépendant de ce module « ';
$impex_phrases['cant_run']									= ' ». Le script ne peut pas continuer tant que cette erreur n\'est pas résolue.';
$impex_phrases['user_id']									= 'Identifiant utilisateur';
$impex_phrases['updating_parent_id']						= 'Mise à jour des identifiants parents, veuillez patienter.';
$impex_phrases['avatar_ok']									= 'Avatar OK';
$impex_phrases['avatar_too_big']							= 'Avatar trop gros';
$impex_phrases['no_rerun']									= 'Vous NE pouvez PAS exécuter de nouveau ce module, vous devez nettoyer la session entière d\'importation et commencer une nouvelle session.';
$impex_phrases['no_system']									= 'ImpEx a tenté de démarrer un système introuvable, ceci est généralement dû à la sauvegarde de la session après avoir sélectionné le système. Veuillez vérifier la base de données et recommencez.';
$impex_phrases['units_per_page']							= 'Unités par page (défini à 500 pour 4 Mo disponible dans PHP)';
$impex_phrases['invalid_target_selected']					= 'La cible sélectionnée n\'est pas la bonne pour la source sélectionnée, veuillez recommencer l\'importation avec une nouvelle session et sélectionnez le système et la version correctes à exporter.';
$impex_phrases['resume_failed']								= 'resume failed';

#####################################
# 001 Setup
#####################################

$impex_phrases['check_update_db']							= 'Vérification et mise à jour de la base de données';
$impex_phrases['get_db_info']								= 'Obtention des informations de la base de données';
$impex_phrases['check_tables']								= 'Ce module va vérifier les tables dans la base de données ainsi que la connexion.';

$impex_phrases['altering_tables']							= 'Modification des tables';
$impex_phrases['alter_desc_1']								= 'ImpEx va maintenant modifier les tables de vBulletin pour pouvoir y inclure les identifiants d\'importation. ';					# Note the trailing space
$impex_phrases['alter_desc_2']								= 'Ceci est nécessaire durant la phase d\'importation pour maintenir les références entre les tables pendant l\'importation. ';		# Note the trailing space
$impex_phrases['alter_desc_3']								= 'Si vous avez des tables plutôt grandes (comme beaucoup de messages), ceci peut prendre du temps. ';								# Note the trailing space
$impex_phrases['alter_desc_4']								= 'Ils seront aussi laissés après l\'importation si vous souhaitez effectuer un retour vers l\'identifiant vB original.';
$impex_phrases['valid_tables_found']    					= 'Tables valides trouvées :';
$impex_phrases['found']                 					= 'trouvé(e)';
$impex_phrases['customtable_prefix']    					= '<b>Tables potentiellement personnelles ou avec un mauvais préfixe :</b>';
$impex_phrases['not_found']             					= '<b>NON</b> trouvé(e)';
$impex_phrases['all_red_tables']							= 'Si vous avez toutes les tables listés en rouge, cela peut venir du préfixe de table à vérifier :';

#####################################
# Associate users
#####################################

$impex_phrases['associate_users']							= 'Association des utilisateurs';

$impex_phrases['assoc_desc_1']								= 'Attention !! Les utilisateurs déjà associés seront supprimés si vous exécutez le module deux fois, ceci supprime les utilisateurs avec un identifiant d\'importation. Vous ne pouvez pas associer d\'administrateurs à cette étape.';
$impex_phrases['assoc_desc_2']								= 'Si vous souhaitez associer un utilisateur source (colonne de gauche) avec un utilisateur vBulletin existant, veuillez insérer son identifiant utilisateur dans le champ fourni et cliquez sur le bouton « Associer ».';

$impex_phrases['assoc_list']								= 'Liste d\'association';
$impex_phrases['assoc_match']								= 'Insérez l\'identifiant existant à côté de la source correspondante auquel vous souhaitez associer le compte :';

$impex_phrases['no_users']									= 'Il n\'y a plus d\'utilisateurs vBulletin à associer, cliquez sur le bouton « Quitter » pour continuer.';
$impex_phrases['assoc_not_matched']							= 'NOT done. It is most likely that vBulletin user';

$impex_phrases['associating_users']							= 'Association des utilisateurs en cours...';

$impex_phrases['associating_user_1']						= 'Association de l\'utilisateur ';
$impex_phrases['associating_user_2']						= ' (userid ';
$impex_phrases['associating_user_3']						= ') avec l\'identifiant utilisateur ';
$impex_phrases['associating_user_4']						= '';
$impex_phrases['no_user_import']							= 'Il n\'y a pas d\'utilisateur à importer.';


#####################################
# Import usergroups
#####################################

$impex_phrases['usergroup']									= 'Groupe d\'utilisateurs';
$impex_phrases['usergroups']								= 'Groupes d\'utilisateurs';
$impex_phrases['usergroups_lower']							= 'groupes d\'utilisateurs';
$impex_phrases['import_usergroup']							= 'Importation de groupe d\'utilisateurs';
$impex_phrases['import_usergroups']							= 'Importation des groupes d\'utilisateurs';
$impex_phrases['usergroups_cleared']						= 'Les groupes d\'utilisateurs importés ont été retirés.';

$impex_phrases['usergroups_per_page']						= 'Groupes d\'utilisateurs à importer par cycle (doit être supérieur à 1)';
$impex_phrases['usergroups_all']							= 'ImpEx va maintenant importer tous les groupes d\'utilisateurs ainsi que les rangs.';

$impex_phrases['no_usergroup_to_import']					= 'Il n\'a a pas de groupe d\'utilisateurs à importer.';

#####################################
# Import users
#####################################

$impex_phrases['user']										= 'Utilisateur';
$impex_phrases['users']										= 'Utilisateurs';
$impex_phrases['users_lower']								= 'utilisateurs';
$impex_phrases['import_user']								= 'Import utilisateur';
$impex_phrases['import_users']								= 'Importation des utilisateurs';
$impex_phrases['users_cleared']								= 'Les utilisateurs importés ont été retirés.';

$impex_phrases['users_per_page']							= 'Utilisateurs à importer par cycle (doit être supérieur à 1)';
$impex_phrases['email_match']								= 'Souhaitez-vous importer les utilisateurs importés associés aux utilisateurs existants si l\'<b>adresse email</b> correspond ?';
$impex_phrases['userid_match']								= 'Souhaitez-vous importer les utilisateurs importés associés aux utilisateurs existants si l\'<b>identifiant utilisateur source et cible</b> correspondent ?';
$impex_phrases['avatar_path']								= 'Chemin vers le répertoire des avatars (soyez certain que le serveur web a un accès en lecture) ?';
$impex_phrases['custom_avatar_path']						= 'Chemin vers le répertoire des avatars personnalisés (soyez certain que le serveur web a un accès en lecture) ?';
$impex_phrases['get_avatars']								= 'Souhaitez-vous importer les avatars (ceci peut prendre un peu de temps si ils sont stockées à distance) ?';
$impex_phrases['which_email']								= 'Quelle adresse email souhaitez-vous importer';
$impex_phrases['which_username']							= 'Quel utilisateur souhaitez-vous importer';
$impex_phrases['avatar_size']								= 'Sélectionnez la plus grande taille d\'avatar autorisée (le fait de définir ceci va forcer ImpEx à les importer).';
$impex_phrases['path_x_not_found']	    					= 'Chemin : %1$s non trouvé';

$impex_phrases['no_user_to_import']							= 'Il n\'y a pas d\'utilisateur à importer.';
$impex_phrases['userid_error']								= 'Erreur d\'identifiant utilisateur';

#####################################
# Import banlists
#####################################

$impex_phrases['banlist']									= 'Liste d\'exclusion';
$impex_phrases['banlists']									= 'Listes d\'exclusion';
$impex_phrases['banlists_lower']							= 'listes d\'exclusion';
$impex_phrases['import_banlist']							= 'Importation de liste d\'exclusion';
$impex_phrases['import_banlists']							= 'Importation des listes d\'exclusions';
$impex_phrases['banlists_cleared']							= 'Les listes d\'exclusion importées ont été retirées.';

$impex_phrases['useridban']									= 'Identifiant utilisateur de la liste d\'exclusion';
$impex_phrases['ipban']										= 'Adresse IP de la liste d\'exclusion';
$impex_phrases['emailban']									= 'Adresse email de la liste d\'exclusion';

$impex_phrases['banlists_per_page']							= 'Souhaitez-vous importer la liste d\'exclusion ?';
$impex_phrases['banlists_number']							= 'Combien de liste à afficher par page ?';
$impex_phrases['banlists_skip']								= 'Vous avez décidé de ne pas importer de liste d\'exclusion.';

$impex_phrases['no_banlist_to_import']						= 'Il n\'y a pas de liste d\'exclusion à importer.';

#####################################
# Import avatars
#####################################

$impex_phrases['avatar']									= 'Avatar';
$impex_phrases['avatars']									= 'Avatars';
$impex_phrases['avatars_lower']								= 'avatars';
$impex_phrases['import_avatar']								= 'Importation d\'avatar';
$impex_phrases['import_avatars']							= 'Importation des avatars';
$impex_phrases['avatars_cleared']							= 'Les avatars importés ont été retirés.';

$impex_phrases['avatar_per_page']							= 'Avatars à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_avatar_to_import']						= 'Il n\'y a pas d\'avatar à importer.';
$impex_phrases['invalid_avatar_skipping']					= 'Avatar non valide, non importé.';

#####################################
# Import custom avatars
#####################################

$impex_phrases['custom_avatar']								= 'Avatar personnalisé';
$impex_phrases['custom_avatars']							= 'Avatars personnalisés';
$impex_phrases['custom_avatars_lower']						= 'avatars personnalisés';
$impex_phrases['import_custom_avatar']						= 'Importation d\'avatar personnalisé';
$impex_phrases['import_custom_avatars']						= 'Importation des avatars personnalisés';

$impex_phrases['no_custom_avatar_import'] 					= 'Il n\'y a pas d\'avatar personnalisé à importer.';

#####################################
# Import custom pictures
#####################################

$impex_phrases['cus_pic']									= 'Image de profil';
$impex_phrases['cust_pics']									= 'Images de profil';
$impex_phrases['cust_pics_lower']							= 'images de profil';
$impex_phrases['import_cust_pic']							= 'Importation d\'image de profil';
$impex_phrases['import_cust_pics']							= 'Importation des images de profil personnalisées';
$impex_phrases['cust_pic_cleared']							= 'Les images de profil importées ont été retirées.';

$impex_phrases['cust_pics_per_page']						= 'Images de profil à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_cust_pic_import']						= 'Il n\'y a pas d\'image de profil à importer.';

#####################################
# Import ranks
#####################################

$impex_phrases['rank']										= 'Rang';
$impex_phrases['ranks']										= 'Rangs';
$impex_phrases['ranks_lower']								= 'rangs';
$impex_phrases['import_rank']								= 'Importer le rang';
$impex_phrases['import_ranks']								= 'Importation des rangs';
$impex_phrases['ranks_cleared']								= 'Les rangs importés ont été retirés.';

$impex_phrases['ranks_per_page']							= 'Les rangs à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_rank_to_import']							= 'Il n\'y a pas de rang à importer.';

#####################################
# Import forums
#####################################

$impex_phrases['forum']										= 'Forum';
$impex_phrases['forums']									= 'Forums';
$impex_phrases['forums_lower']								= 'forums';
$impex_phrases['category']									= 'Catégorie';
$impex_phrases['categories']								= 'Catégories';
$impex_phrases['categories_lower']							= 'catégories';
$impex_phrases['import_forum']								= 'Importation de forum';
$impex_phrases['import_forums']								= 'Importation des forums';
$impex_phrases['forums_cleared']							= 'Les forums importés ont été retirés.';

$impex_phrases['forums_per_page']							= 'Forums à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_forum_to_import']						= 'Il n\'y a pas de forum à importer.';

#####################################
# Import threads
#####################################

$impex_phrases['thread']									= 'Discussion';
$impex_phrases['threads']									= 'Discussions';
$impex_phrases['threads_lower']								= 'discussions';
$impex_phrases['import_thread']								= 'Importation de discussion';
$impex_phrases['import_threads']							= 'Importation des discussions';
$impex_phrases['threads_cleared']							= 'Les discussions importées ont été retirées.';

$impex_phrases['threads_per_page']							= 'Discussions à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_thread_to_import']						= 'Il n\'y a pas de discussion à importer.';

$impex_phrases['updating_pollids']							= 'Mise à jour des identifiants des sondages pour les nouvelles discussions.';

#####################################
# Import post
#####################################

$impex_phrases['post']										= 'Message';
$impex_phrases['posts']										= 'Messages';
$impex_phrases['posts_lower']								= 'messages';
$impex_phrases['import_post']								= 'Importation de message';
$impex_phrases['import_posts']								= 'Importation des messages';
$impex_phrases['posts_cleared']								= 'Les messages importés ont été retirés.';

$impex_phrases['posts_per_page']							= 'Messages à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_post_to_import']							= 'Il n\'y a pas de message à importer.';

#####################################
# Import smilies
#####################################

$impex_phrases['smilie']									= 'Smiley';
$impex_phrases['smilies']									= 'Smileys';
$impex_phrases['smilies_lower']								= 'smileys';
$impex_phrases['import_smilie']								= 'Importation de smiley';
$impex_phrases['import_smilies']							= 'Importation des smileys';
$impex_phrases['smilies_cleared']							= 'Les smileys importés ont été retirés.';

$impex_phrases['smilies_per_page']							= 'Smileys à importer par cycle (doit être supérieur à 1)';
$impex_phrases['smilies_desc']								= 'Le système va maintenant commencer à importer les smileys depuis la source. Veuillez vous rappeler de placer les images correspondantes dans le répertoire des smileys vB (images/smilies/).';
$impex_phrases['smilie_overwrite']							= 'Souhaitez-vous que les smileys source écrasent les smileys déjà existants si il y a un cas de doublon ?';

$impex_phrases['too_long']									= 'Trop long';
$impex_phrases['truncating']								= 'coupé à';
$impex_phrases['duplication']								= 'Duplication';
$impex_phrases['no_smilie_to_import']						= 'Il n\'y a pas de smileys à importer.';

#####################################
# Import attachment
#####################################

$impex_phrases['attachment']								= 'Pièce jointe';
$impex_phrases['attachments']								= 'Pièces jointes';
$impex_phrases['attachments_lower']							= 'pièces jointes';
$impex_phrases['import_attachment']							= 'Importation de pièce jointe';
$impex_phrases['import_attachments']						= 'Importation des pièces jointes';
$impex_phrases['attachments_cleared']						= 'Les pièces jointes importés ont été retirées.';

$impex_phrases['attachments_per_page']						= 'Pièces jointes à importer par cycle (doit être supérieur à 1)';
$impex_phrases['path_to_upload']							= 'Chemin entier vers le répertoire des pièces jointes où se trouve les pièces jointes source.';
$impex_phrases['source_file_not']							= 'Fichier source non trouvé.';

$impex_phrases['no_attachment_to_import']					= 'Il n\'y a pas de pièce jointe à importer.';

#####################################
# Import poll
#####################################

$impex_phrases['poll']										= 'Sondage';
$impex_phrases['polls']										= 'Sondages';
$impex_phrases['polls_lower']								= 'sondages';
$impex_phrases['import_poll']								= 'Importation de sondage';
$impex_phrases['import_polls']								= 'Importation des sondages';
$impex_phrases['polls_cleared']								= 'Les sondages importés ont été retirés.';

$impex_phrases['polls_per_page']							= 'Sondages à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_poll_to_import']							= 'Il n\'y a pas de sondage à importer.';

#####################################
# Import moderators
#####################################

$impex_phrases['moderator']									= 'Modérateur';
$impex_phrases['moderators']								= 'Modérateurs';
$impex_phrases['moderators_lower']							= 'modérateurs';
$impex_phrases['import_moderator']							= 'Importation de modérateur';
$impex_phrases['import_moderators']							= 'Importation des modérateurs';
$impex_phrases['moderators_cleared']						= 'Les modérateurs importés ont été retirés.';

$impex_phrases['moderators_per_page']						= 'Modérateurs à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_moderator_to_import']					= 'Il n\'y a pas de modérateur à importer.';

#####################################
# Import phrase
#####################################

$impex_phrases['phrase']									= 'Expression';
$impex_phrases['phrases']									= 'Expressions';
$impex_phrases['phrases_lower']								= 'expressions';
$impex_phrases['import_phrase']								= 'Importation d\‘expression';
$impex_phrases['import_phrases']							= 'Importation des expressions';
$impex_phrases['phrases_cleared']							= 'Les expressions importées ont été retirées.';

$impex_phrases['phrases_per_page']							= 'Expressions à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_phrase_to_import']						= 'Il n\'y a pas d\'expression à importer.';

#####################################
# Import Subscription
#####################################

$impex_phrases['subscription']								= 'Abonnement';
$impex_phrases['subscriptions']								= 'Abonnements';
$impex_phrases['subscriptions_lower']						= 'abonnement';
$impex_phrases['import_subscription']						= 'Importation d\'abonnement';
$impex_phrases['import_subscriptions']						= 'Importation des abonnements';
$impex_phrases['subscriptions_cleared']						= 'Les abonnements importés ont été retirés.';

$impex_phrases['subscriptions_per_page']					= 'Abonnements à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_subscription_to_import']					= 'Il n\'y a pas d\'abonnement à importer.';

$impex_phrases['subscriptionlogs']							= 'Journaux d\'abonnement';

#####################################
# Import Private Message
#####################################

$impex_phrases['pm']										= 'Message privé';
$impex_phrases['pms']										= 'Messages privés';
$impex_phrases['pms_lower']									= 'messages privés';
$impex_phrases['import_pm']									= 'Importation de message privé';
$impex_phrases['import_pms']								= 'Importation des messages privés';
$impex_phrases['pms_cleared']								= 'Les messages privés importés ont été retirés.';

$impex_phrases['pm_counter_updated']						= 'Les compteurs ont été mis à jour.';
$impex_phrases['pm_counter_error']							= 'Erreur lors de la mise à jour du compteur.';

$impex_phrases['pms_per_page']								= 'Messages privés à importer par cycle (doit être supérieur à 1)';
$impex_phrases['no_pm_to_import']							= 'Il n\'y a pas de message privé à importer.';

#####################################
# Import Errors & Remedys
#####################################

$impex_phrases['associate_error']							= 'User could not be associated';
$impex_phrases['associate_error_rem']						= 'Ensure user is not an admin and they have a unique id';

$impex_phrases['table_check_error']							= 'Initial source database check failed';
$impex_phrases['check_db_permissions']						= 'Check database permissions and connection, or table prefix to ensure its correct';
$impex_phrases['invalid_object']							= 'Invalid, skipping. Failed on : ';
$impex_phrases['invalid_object_rem']						= 'Ensure that the failed field is present in the source database or defaulted is being set';
$impex_phrases['table_alter_fail']							= 'Failed trying to alter a table to add a column: '; #Note space
$impex_phrases['table_alter_fail_rem']						= 'Ensure that you have ALTER permission on the target database';

$impex_phrases['usergroup_not_imported']					= 'Usergroup not imported';
$impex_phrases['usergroup_not_imported_rem']				= 'Check source users profile is as complete as possible';
$impex_phrases['usergroup_restart_failed']					= 'Restart failed, clear_imported_usergroups';
$impex_phrases['usergroup_restart_ok']						= 'Imported usergroups have been cleared';

$impex_phrases['rank_not_imported']							= 'Rank not imported';
$impex_phrases['rank_not_imported_rem'] 					= 'Check source rank is as complete as possible';
$impex_phrases['rank_restart_failed']						= 'Restart failed, clear_imported_ranks';
$impex_phrases['rank_restart_ok']							= 'Imported ranks have been cleared';

$impex_phrases['user_not_imported']							= 'User not imported';
$impex_phrases['user_not_imported_rem']						= 'Check source users profile is as complete as possible';
$impex_phrases['user_restart_failed']						= 'Restart failed, clear_imported_users';
$impex_phrases['user_restart_ok']							= 'Imported users have been cleared';
$impex_phrases['user_check_db_perms']						= 'Check database permissions and user table';

$impex_phrases['smilie_not_imported']						= 'Smilie not imported';
$impex_phrases['smilie_not_imported_rem']					= 'Check source smilie details are as complete as possible';
$impex_phrases['smilie_restart_failed']						= 'Restart failed, clear_imported_smilie';
$impex_phrases['smilie_restart_ok']							= 'Imported smilies have been cleared';

$impex_phrases['post_not_imported']							= 'Post not imported';
$impex_phrases['post_not_imported_rem']						= 'Use the import id to check the source post content and size';
$impex_phrases['post_restart_failed']						= 'Restart failed, clear_imported_posts';
$impex_phrases['post_restart_ok']							= 'Imported posts have been cleared';

$impex_phrases['forum_not_imported']						= 'Forum not imported';
$impex_phrases['forum_not_imported_rem']					= 'Use the import id to check the source forum content and size';
$impex_phrases['forum_restart_failed']						= 'Restart failed, clear_imported_forums';
$impex_phrases['forum_restart_ok']							= 'Imported forums have been cleared';

$impex_phrases['thread_not_imported']						= 'Thread not imported';
$impex_phrases['thread_not_imported_rem']					= 'Use the import id to check the source thread content and size and forum parent';
$impex_phrases['thread_restart_failed']						= 'Restart failed, clear_imported_threads';
$impex_phrases['thread_restart_ok']							= 'Imported threads have been cleared';

$impex_phrases['moderator_not_imported']					= 'Moderator not imported';
$impex_phrases['moderator_not_imported_rem']				= 'Use the import id to check the source moderator and forum they are linked to';
$impex_phrases['moderator_restart_failed']					= 'Restart failed, clear_imported_moderators';
$impex_phrases['moderator_restart_ok']						= 'Imported moderators have been cleared';

$impex_phrases['poll_not_imported']							= 'The poll was imported though not attached to the correct thread.';
$impex_phrases['poll_not_imported_1']						= 'The poll was imported though not attached to the correct thread.';
$impex_phrases['poll_not_imported_rem']						= 'Use the import id to check the source poll id and thread it matches in the source';
$impex_phrases['poll_not_imported_2']						= 'The poll was not imported.';
$impex_phrases['poll_not_imported_3']						= 'The poll voters were not attached to the correct thread.';

$impex_phrases['poll_restart_failed']						= 'Restart failed, clear_imported_polls';
$impex_phrases['poll_restart_ok']							= 'Imported polls have been cleared';

$impex_phrases['attachment_not_imported']					= 'Attachment not imported';
$impex_phrases['attachment_not_imported_rem_1']				= 'Check the path is correct and the file is present and readable by the webserver ';
$impex_phrases['attachment_not_imported_rem_2']				= 'Use the import id to check the source attachment and ensure the post is present';
$impex_phrases['attachment_restart_failed']					= 'Restart failed, clear_imported_attachments';
$impex_phrases['attachment_restart_ok']						= 'Imported attachments have been cleared';

$impex_phrases['pm_not_imported']							= 'Private message not imported';
$impex_phrases['pm_not_imported_rem_1']						= 'Use the import id to check the source Private message userid';
$impex_phrases['pm_not_imported_rem_2']						= 'pmtext imported though pm not assigend to user, find the importpmid';
$impex_phrases['pm_restart_failed']							= 'Restart failed, clear_imported_private_messages';
$impex_phrases['pm_restart_ok']								= 'Imported Private message have been cleared';

$impex_phrases['avatar_not_imported']						= 'Avatar not imported';
$impex_phrases['avatar_not_imported_rem']					= 'Use the import id to check the source database and avatar size';
$impex_phrases['avatar_restart_failed']						= 'Restart failed, clear_imported_avatars';
$impex_phrases['avatar_restart_ok']							= 'Imported Avatars have been cleared';

$impex_phrases['custom_avatar_not_imported']				= 'Custom avatar not imported';
$impex_phrases['custom_avatar_not_imported_rem']			= 'Use the import id to check the source database and avatar size';

$impex_phrases['custom_profile_pic_not_imported']			= 'Custom profile pic not imported';
$impex_phrases['custom_profile_pic_not_imported_rem'] 		= 'Use the import id to check the source database and pic size';
$impex_phrases['custom_profile_pic_restart_failed']			= 'Restart failed, clear_imported_custom_pics';
$impex_phrases['custom_profile_pic_restart_ok']				= 'Imported Custom Profile Pics have been cleared';

$impex_phrases['phrase_not_imported']						= 'Phrase not imported';
$impex_phrases['phrase_not_imported_rem']					= 'Use the import id to check the source database and check for a duplicate in the target';
$impex_phrases['phrase_restart_failed']						= 'Restart failed, clear_imported_phrases';
$impex_phrases['phrase_restart_ok']							= 'Imported Phrase have been cleared';

#####################################
# Specific importer text
#####################################

$impex_phrases['discus_mess_file']							= 'Full path and file name of the discus tab messages file';
$impex_phrases['discus_admin_path']							= 'Full Path to discus admin folder (where the users.txt file is located)';

$impex_phrases['ipb_default_admin']							= 'Default admin, userid may need checking';

$impex_phrases['username_email']							= 'Would you like to use the eve/groupee USERNAME instead of the email address for the username in vBulletin';

#####################################
# Blog phrases
#####################################

$impex_phrases['blog'] 										= 'Blog';
$impex_phrases['blogs'] 									= 'Blogs';
$impex_phrases['import_blog'] 								= 'Import blog';
$impex_phrases['import_blog_attachment'] 					= 'Import blog attachment';
$impex_phrases['import_blog_category'] 						= 'Import blog category';
$impex_phrases['import_blog_category_user']					= 'Import blog category user';
$impex_phrases['import_blog_rate'] 							= 'Import blog rating';
$impex_phrases['import_blog_moderator'] 					= 'Import blog moderator';
$impex_phrases['import_blog_subscribepost'] 				= 'Import blog subscribepost';
$impex_phrases['import_blog_subscribeuser'] 				= 'Import blog subscribeuser';
$impex_phrases['import_blog_text'] 							= 'Import blog text';
$impex_phrases['import_blog_trackback'] 					= 'Import blog trackback';
$impex_phrases['import_blog_custom_block'] 					= 'Import blog custom block';
$impex_phrases['import_blog_user'] 							= 'Import blog user';
$impex_phrases['import_blog_comments'] 						= 'Import blog comments';
$impex_phrases['import_blog_group_membership']				= 'Import blog group memberships';
$impex_phrases['blog_comments']								= 'Blog comments';
$impex_phrases['blog_users']								= 'Blog users';
$impex_phrases['blog_categories']							= 'Blog categories';
$impex_phrases['blog_attachments']							= 'Blog attachments';
$impex_phrases['blog_category_users']						= 'Blog category users';
$impex_phrases['blog_moderators']							= 'Blog moderators';
$impex_phrases['blog_rates']								= 'Blog ratings';
$impex_phrases['blog_trackbacks']							= 'Blog trackbacks';
$impex_phrases['blog_custom_blocks']						= 'Blog custom blocks';
$impex_phrases['blog_group_memberships']					= 'Blog group memberships';

$impex_phrases['blog_not_imported']							= 'Blog not imported';
$impex_phrases['blog_not_imported_rem']						= 'Check source Blog is as complete as possible';
$impex_phrases['blog_user_not_imported']					= 'Blog user not imported';
$impex_phrases['blog_user_not_imported_rem']				= 'Check source users profile is as complete as possible';
$impex_phrases['blog_comment_not_imported']					= 'Blog comment not imported';
$impex_phrases['blog_comment_not_imported_rem']				= 'Check source blog text is as complete as possible';
$impex_phrases['blog_category_not_imported']				= 'Blog category not imported';
$impex_phrases['blog_category_not_imported_rem']			= 'Check source blog category is as complete as possible';
$impex_phrases['blog_category_user_not_imported']			= 'Blog category user not imported';
$impex_phrases['blog_category_user_not_imported_rem']		= 'Check source blog category user is as complete as possible';
$impex_phrases['blog_moderator_not_imported']				= 'Blog moderator user not imported';
$impex_phrases['blog_moderator_user_not_imported_rem']		= 'Check source moderator is as complete as possible';
$impex_phrases['blog_rate_not_imported']					= 'Blog rating not imported';
$impex_phrases['blog_rate_not_imported_rem']				= 'Check source rating is as complete as possible';
$impex_phrases['blog_trackback_not_imported']				= 'Blog trackback not imported';
$impex_phrases['blog_trackback_not_imported_rem']			= 'Check source trackback is as complete as possible';
$impex_phrases['blog_custom_block_not_imported']			= 'Blog custom block not imported';
$impex_phrases['blog_custom_block_not_imported_rem']		= 'Check source custom block is as complete as possible';
$impex_phrases['blog_group_membership_not_imported']		= 'Blog group membership block not imported';
$impex_phrases['blog_group_membership_not_imported_rem']	= 'Check source group membership is as complete as possible';

$impex_phrases['blogs_cleared']								= 'Imported blogs have been cleared';
$impex_phrases['blog_comments_cleared']						= 'Imported blog comments have been cleared';
$impex_phrases['blog_users_cleared']						= 'Imported blog users have been cleared';
$impex_phrases['blog_categories_cleared']					= 'Imported blog categories have been cleared';
$impex_phrases['blog_category_users_cleared']				= 'Imported blog category users have been cleared';
$impex_phrases['blog_attachments_cleared']					= 'Imported blog attachments have been cleared';
$impex_phrases['blog_moderators_cleared']					= 'Imported blog moderators have been cleared';
$impex_phrases['blog_rates_cleared']						= 'Imported blog ratings have been cleared';
$impex_phrases['blog_trackbacks_cleared']					= 'Imported blog trackbacks have been cleared';
$impex_phrases['blog_custom_blocks_cleared']				= 'Imported blog custom blocks have been cleared';
$impex_phrases['blog_group_memberships_cleared']			= 'Imported blog group memberships have been cleared';

$impex_phrases['blog_user_restart_failed']					= 'Restart failed, clear_imported_blog_users()';
$impex_phrases['blog_restart_failed']						= 'Restart failed, clear_imported_blogs()';
$impex_phrases['blog_category_restart_failed']				= 'Restart failed, clear_imported_blog_category()';
$impex_phrases['blog_category_user_restart_failed']			= 'Restart failed, clear_imported_blog_category_user()';
$impex_phrases['blog_comment_restart_failed']				= 'Restart failed, clear_imported_blog_comments()';
$impex_phrases['blog_moderator_restart_failed']				= 'Restart failed, clear_imported_blog_moderators()';
$impex_phrases['blog_rate_restart_failed']					= 'Restart failed, clear_imported_blog_rates()';
$impex_phrases['blog_trackback_restart_failed']				= 'Restart failed, clear_imported_blog_trackbacks()';
$impex_phrases['blog_custom_block_restart_failed']			= 'Restart failed, clear_imported_blog_custom_blocks()';
$impex_phrases['blog_group_membership_restart_failed']		= 'Restart failed, clear_imported_blog_group_memberships()';

#####################################
# CMS phrases
#####################################

$impex_phrases['import_cms_article'] 						= 'Import CMS article';
$impex_phrases['import_cms_section'] 						= 'Import CMS section';
$impex_phrases['import_cms_section_order']					= 'Import CMS section order';
$impex_phrases['import_cms_widget'] 						= 'Import CMS widget';
$impex_phrases['import_cms_category'] 						= 'Import CMS category';
$impex_phrases['import_cms_layout'] 						= 'Import CMS layout';
$impex_phrases['import_cms_grid']							= 'Import CMS grid';

$impex_phrases['cms_category']								= 'CMS category';
$impex_phrases['cms_categories']							= 'CMS categories';
$impex_phrases['cms_article']								= 'CMS article';
$impex_phrases['cms_articles']								= 'CMS articles';
$impex_phrases['cms_section']								= 'CMS section';
$impex_phrases['cms_sections']								= 'CMS sections';
$impex_phrases['cms_widget']								= 'CMS widget';
$impex_phrases['cms_widgets']								= 'CMS widgets';
$impex_phrases['cms_section_order']							= 'CMS section';
$impex_phrases['cms_section_orders']						= 'CMS section orders';
$impex_phrases['cms_layout']								= 'CMS layout';
$impex_phrases['cms_layouts']								= 'CMS layouts';
$impex_phrases['cms_grid']									= 'CMS grid';
$impex_phrases['cms_grids']									= 'CMS grids';

$impex_phrases['cms_categories_cleared']					= 'Imported CMS categories have been cleared';
$impex_phrases['cms_articles_cleared']						= 'Imported CMS articles have been cleared';
$impex_phrases['cms_sections_cleared']						= 'Imported CMS sections have been cleared';
$impex_phrases['cms_widgets_cleared']						= 'Imported CMS widgets have been cleared';
$impex_phrases['cms_section_orders_cleared']				= 'Imported CMS section orders have been cleared';
$impex_phrases['cms_layouts_cleared']						= 'Imported CMS layouts have been cleared';
$impex_phrases['cms_grids_cleared']							= 'Imported CMS grids have been cleared';

$impex_phrases['cms_category_not_imported']					= 'CMS category not imported';
$impex_phrases['cms_category_not_imported_rem']				= 'Check source CMS category is as complete as possible';
$impex_phrases['cms_article_not_imported']					= 'CMS article not imported';
$impex_phrases['cms_article_not_imported_rem']				= 'Check source CMS article is as complete as possible';
$impex_phrases['cms_section_not_imported']					= 'CMS section not imported';
$impex_phrases['cms_section_not_imported_rem']				= 'Check source CMS section is as complete as possible';
$impex_phrases['cms_widget_not_imported']					= 'CMS widget not imported';
$impex_phrases['cms_widget_not_imported_rem']				= 'Check source CMS widget is as complete as possible';
$impex_phrases['cms_section_order_not_imported']			= 'CMS section order not imported';
$impex_phrases['cms_section_order_not_imported_rem']		= 'Check source CMS section order is as complete as possible';
$impex_phrases['cms_layout_not_imported']					= 'CMS layout not imported';
$impex_phrases['cms_layout_not_imported_rem']				= 'Check source CMS layout is as complete as possible';
$impex_phrases['cms_grid_not_imported']						= 'CMS grid not imported';
$impex_phrases['cms_grid_not_imported_rem']					= 'Check source CMS grid is as complete as possible';
$impex_phrases['cms_node_not_imported']						= 'CMS node not imported';
$impex_phrases['cms_node_not_imported_rem']					= 'Check source CMS node is as complete as possible';

$impex_phrases['cms_category_restart_failed']				= 'Restart failed, clear_imported_cms_categories()';
$impex_phrases['cms_article_restart_failed']				= 'Restart failed, clear_imported_cms_articles()';
$impex_phrases['cms_section_restart_failed']				= 'Restart failed, clear_imported_cms_sections()';
$impex_phrases['cms_widget_restart_failed']					= 'Restart failed, clear_imported_cms_widgets()';
$impex_phrases['cms_section_orders_restart_failed']			= 'Restart failed, clear_imported_cms_section_orders()';
$impex_phrases['cms_layout_restart_failed']					= 'Restart failed, clear_imported_cms_layouts()';
$impex_phrases['cms_grid_restart_failed']					= 'Restart failed, clear_imported_cms_grids()';

$impex_phrases['import_cms_attachment'] 					= 'Import cms attachment';
$impex_phrases['cms_attachments']							= 'CMS attachments';
$impex_phrases['cms_attachments_cleared']					= 'Imported cms attachments have been cleared';

?>
