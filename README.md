# CRM inmobiliario con Laravel y Livewire

El CRM de Atalaya Inmobiliaria, una agencia inventada de Valencia: la cartera de inmuebles, los clientes con lo que busca cada uno, el cruce automático entre las dos cosas y la agenda de visitas. Al dar de alta un piso ves qué clientes lo están buscando, y al dar de alta un cliente ves qué inmuebles le encajan.

Está hecho con Laravel 13 y Livewire 4, con las pantallas como componentes de un solo archivo y Tailwind para los estilos.

El tutorial completo, paso a paso, está en Neoguias: [Cómo crear un CRM inmobiliario con Laravel](https://www.neoguias.com/crear-crm-inmobiliario-laravel/).

## Cómo arrancarlo

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Abre `http://localhost:8000`. El seeder carga diez inmuebles, cinco clientes con lo que buscan y dos visitas, así que el cruce ya tiene algo que enseñar desde el principio.

Los tests usan una base de datos SQLite en memoria:

```bash
php artisan test
```

Requiere PHP 8.3 y Node 20 o superior.

## Dónde está cada cosa

- [`app/Models/Property.php`](app/Models/Property.php) y [`app/Models/Demand.php`](app/Models/Demand.php): los inmuebles y lo que busca cada cliente. El scope `matching` de cada uno hace el cruce, en un sentido y en el otro.
- [`app/Enums`](app/Enums): operación, tipo de inmueble, estado y resultado de una visita, con sus textos en español.
- [`app/Livewire/Forms`](app/Livewire/Forms): los formularios de alta de inmuebles y de clientes, con su validación.
- [`resources/views/pages`](resources/views/pages): las cuatro pantallas del CRM: inmuebles, ficha de un inmueble, clientes y visitas.
- [`config/crm.php`](config/crm.php): el nombre de la agencia y las zonas en las que trabaja.
- [`tests/Feature/CrmTest.php`](tests/Feature/CrmTest.php): el cruce, los formularios y la agenda.

## Licencia

MIT.
