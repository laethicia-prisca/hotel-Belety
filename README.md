# 🏨 Système de Gestion d'Hôtel

Application web complète pour la gestion d'hôtel développée avec HTML, CSS, JavaScript, PHP et MySQL.

## 📋 Fonctionnalités

### Gestion des Chambres
- ✅ Ajout, modification, suppression de chambres
- ✅ Différents types de chambres (Simple, Double, Twin, Suite, etc.)
- ✅ Gestion des statuts (Disponible, Occupée, Maintenance, Nettoyage)
- ✅ Filtrage et recherche avancée

### Gestion des Clients
- ✅ Enregistrement complet des informations clients
- ✅ Historique des réservations par client
- ✅ Recherche et filtrage
- ✅ Profil détaillé de chaque client

### Système de Réservations
- ✅ Création et modification de réservations
- ✅ Vérification automatique de disponibilité
- ✅ Calcul automatique du prix total
- ✅ Gestion des statuts (En attente, Confirmée, Arrivé, Parti, Annulée)
- ✅ Gestion des paiements (Non payé, Partiel, Payé)
- ✅ Demandes spéciales

### Gestion des Paiements
- ✅ Enregistrement des paiements
- ✅ Multiples méthodes (Espèces, Carte, Virement, En ligne)
- ✅ Mise à jour automatique du statut de paiement
- ✅ Historique complet des transactions

### Services Supplémentaires
- ✅ Gestion des services additionnels
- ✅ Prix et descriptions personnalisables
- ✅ Activation/Désactivation des services

### Gestion des Utilisateurs (Admin)
- ✅ Création et gestion des comptes utilisateurs
- ✅ Rôles : Administrateur et Réceptionniste
- ✅ Sécurité avec mots de passe hashés
- ✅ Suivi des connexions

### Rapports et Statistiques (Admin)
- ✅ Dashboard avec statistiques en temps réel
- ✅ Revenus totaux et par période
- ✅ Taux d'occupation
- ✅ Répartition par méthode de paiement
- ✅ Top clients et chambres les plus réservées
- ✅ Filtres personnalisables (jour, semaine, mois, année, personnalisé)

## 🛠️ Technologies Utilisées

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Base de données**: MySQL 5.7+
- **Icônes**: Font Awesome 6.4
- **Design**: Responsive et moderne

## 📦 Installation

### Prérequis
- Serveur web (Apache/Nginx)
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Extension PHP PDO activée

### Étapes d'installation

1. **Extraire les fichiers**
   - Décompressez l'archive dans votre répertoire web (ex: `htdocs`, `www`, etc.)

2. **Créer la base de données**
   ```sql
   -- Option 1: Via phpMyAdmin
   - Créez une base de données nommée 'hotel_management'
   - Importez le fichier 'database.sql'
   
   -- Option 2: Via ligne de commande
   mysql -u root -p
   source /chemin/vers/database.sql
   ```

3. **Configurer la connexion**
   - Ouvrez le fichier `includes/config.php`
   - Modifiez les paramètres de connexion si nécessaire:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'hotel_management');
   ```

4. **Permissions des dossiers**
   ```bash
   chmod 755 uploads/
   ```

5. **Accéder à l'application**
   - Ouvrez votre navigateur
   - Accédez à: `http://localhost/hotel-management-system/`

## 👤 Comptes de Test

### Administrateur
- **Nom d'utilisateur**: `admin`
- **Mot de passe**: `admin123`
- **Accès**: Toutes les fonctionnalités

### Réceptionniste
- **Nom d'utilisateur**: `receptionist`
- **Mot de passe**: `admin123`
- **Accès**: Fonctionnalités opérationnelles (pas d'accès aux utilisateurs et rapports)

## 📁 Structure du Projet

```
hotel-management-system/
├── css/
│   └── style.css                 # Styles globaux
├── js/
│   └── script.js                 # Scripts JavaScript
├── includes/
│   ├── config.php                # Configuration et fonctions
│   ├── header.php                # En-tête commun
│   └── footer.php                # Pied de page commun
├── php/                          # Scripts PHP additionnels
├── uploads/                      # Dossier pour les images
├── database.sql                  # Structure de la base de données
├── login.php                     # Page de connexion
├── logout.php                    # Déconnexion
├── index.php                     # Dashboard principal
├── rooms.php                     # Gestion des chambres
├── clients.php                   # Gestion des clients
├── reservations.php              # Gestion des réservations
├── payments.php                  # Gestion des paiements
├── services.php                  # Gestion des services
├── users.php                     # Gestion des utilisateurs (admin)
├── reports.php                   # Rapports et statistiques (admin)
└── README.md                     # Ce fichier
```

## 🔒 Sécurité

- ✅ Protection contre les injections SQL (requêtes préparées)
- ✅ Mots de passe hashés avec bcrypt
- ✅ Validation et nettoyage des entrées
- ✅ Sessions sécurisées
- ✅ Contrôle d'accès basé sur les rôles
- ✅ Protection CSRF recommandée pour la production

## 🎨 Fonctionnalités du Design

- ✅ Interface moderne et intuitive
- ✅ Design responsive (mobile, tablette, desktop)
- ✅ Navigation latérale persistante
- ✅ Modals pour les formulaires
- ✅ Alertes animées auto-disparaissantes
- ✅ Statistiques visuelles avec cartes
- ✅ Badges de statut colorés
- ✅ Animations fluides

## 📊 Base de Données

### Tables principales
- `users` - Utilisateurs du système
- `rooms` - Chambres de l'hôtel
- `room_types` - Types de chambres
- `clients` - Clients de l'hôtel
- `reservations` - Réservations
- `payments` - Paiements
- `services` - Services supplémentaires
- `reservation_services` - Services liés aux réservations

### Vues
- `v_available_rooms` - Chambres disponibles
- `v_reservation_details` - Détails complets des réservations
- `v_revenue_summary` - Résumé des revenus

### Procédures stockées
- `check_room_availability` - Vérification de disponibilité

### Triggers
- `update_room_status_after_reservation` - MAJ automatique du statut

## 🚀 Améliorations Futures (Suggestions)

- [ ] Système de notifications par email
- [ ] Calendrier visuel des réservations
- [ ] Export PDF des factures
- [ ] Gestion des promotions et remises
- [ ] API REST pour intégrations
- [ ] Application mobile
- [ ] Système de fidélité clients
- [ ] Intégration paiement en ligne
- [ ] Multi-langue
- [ ] Backup automatique

## 🐛 Dépannage

### Erreur de connexion à la base de données
- Vérifiez les paramètres dans `includes/config.php`
- Assurez-vous que MySQL est démarré
- Vérifiez les droits utilisateur MySQL

### Page blanche
- Activez l'affichage des erreurs PHP
- Vérifiez les logs d'erreur du serveur
- Assurez-vous que l'extension PDO est activée

### Problème d'affichage
- Videz le cache du navigateur
- Vérifiez que les fichiers CSS/JS sont chargés
- Testez sur un autre navigateur

## 📝 Notes Importantes

1. **Production**: Avant de déployer en production:
   - Changez tous les mots de passe par défaut
   - Configurez HTTPS
   - Ajoutez une protection CSRF
   - Activez les logs d'erreur
   - Désactivez l'affichage des erreurs PHP

2. **Backup**: Effectuez des sauvegardes régulières de:
   - La base de données
   - Le dossier `uploads/`

3. **Maintenance**: Nettoyez régulièrement:
   - Les anciennes réservations
   - Les logs
   - Les sessions expirées

## 📄 Licence

Ce projet est un exemple éducatif. Vous êtes libre de l'utiliser et de le modifier selon vos besoins.

## 👨‍💻 Support

Pour toute question ou problème:
- Vérifiez d'abord ce README
- Consultez les commentaires dans le code
- Vérifiez les logs d'erreur

## 🎉 Remerciements

Merci d'utiliser ce système de gestion d'hôtel ! N'hésitez pas à l'adapter à vos besoins spécifiques.

---

**Développé avec ❤️ pour faciliter la gestion hôtelière**

Version 1.0.0 - Janvier 2026
