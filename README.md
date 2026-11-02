<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Gestion des utilisateurs (RETISS)

### Comptes de démonstration

Créés par `php artisan migrate:fresh --seed`, mot de passe `password`, e-mail déjà vérifié :

| Compte | Rôle | Arrivée après connexion |
|---|---|---|
| `admin@retiss.tn` | ADMIN | Gestion des utilisateurs (`/admin/users`) |
| `client@retiss.tn` | CLIENT | Tableau de bord (`/dashboard`) |
| `donateur@retiss.tn` | DONATEUR | Tableau de bord (`/dashboard`) |
| `atelier@retiss.tn` | ATELIER | Espace upcycling (`/upcycling`) |
| `recycleur@retiss.tn` | RECYCLEUR | Espace recyclage (`/recyclage`) |
| `collecteur@retiss.tn` | COLLECTEUR | Tournées (`/logistique/tournees`) |

### Vérification de l'adresse e-mail

- `User` implémente `MustVerifyEmail` ; toutes les routes de l'application exigent `auth` + `verified`.
  Restent accessibles sans vérification : la page `verify-email`, le renvoi du lien, la déconnexion, `/api/metrics` et le webhook Stripe.
- Un compte créé par l'inscription reçoit un lien signé valable 60 minutes. Changer d'e-mail dans le profil renvoie un lien.
- Un compte créé par un administrateur est considéré comme vérifié : l'administrateur a saisi l'adresse lui-même, aucun e-mail n'est envoyé.
- E-mails : Mailpit dans Docker (`http://localhost:8026`), ou `MAIL_MAILER=log` en local (les liens apparaissent dans `storage/logs/laravel.log`).

### Rôles et permissions (Spatie Permission)

- La colonne `users.role` reste la source de vérité ; le rôle Spatie est synchronisé à chaque changement (`User::synchroniserRoleSpatie()`).
- `RolesEtPermissionsSeeder` crée les 6 rôles et les permissions `gerer utilisateurs`, `voir statistiques`, `gerer projets upcycling`, `traiter lots`, `gerer tournees` et `publier articles`.
  Sur une base existante : `php artisan db:seed --class=RolesEtPermissionsSeeder`.
- Middlewares : `role` (rôle de la colonne, pseudo-rôle `ACHETEUR`), `permission` et `role_or_permission` (Spatie).
- La gestion des utilisateurs exige `permission:gerer utilisateurs` (route + `UserPolicy`), les statistiques admin `permission:voir statistiques`.
- La méthode maison `hasRole()` est devenue `aLeRole()` ; `hasRole()` est celle de Spatie.

### Authentification à deux facteurs (Fortify)

- Fortify n'est utilisé que pour la 2FA (`Fortify::ignoreRoutes()`), Breeze gère toujours la connexion.
- Profil → onglet **Sécurité** → *Activer la double authentification* : mot de passe redemandé, QR code et clé à scanner
  (Google Authenticator, Microsoft Authenticator…), confirmation par un code à 6 chiffres, 8 codes de récupération à usage unique,
  régénération et désactivation.
- À la connexion, l'utilisateur passe par la page **Code de vérification** (code de l'application ou code de récupération),
  5 tentatives par minute, puis arrive sur l'espace de son rôle.

### Photo de profil

- Profil → *Photo de profil* : JPG, PNG ou WebP, 2 Mo maximum, stockée dans `storage/app/public/avatars`
  (`php artisan storage:link` en local). Bouton *Retirer la photo*.
- L'ancienne photo est supprimée quand elle est remplacée, et la photo est supprimée avec le compte.
- La photo, ou les initiales, s'affiche dans la barre de navigation et sur la page de profil.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
