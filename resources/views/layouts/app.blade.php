<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'BDE Events')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}" />
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">BDE<span>Events</span></div>
    <nav>
      <a href="{{ route('planning') }}" class="nav-link {{ request()->routeIs('planning') ? 'active' : '' }}">Planning</a>
      <a href="{{ route('events.index') }}" class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">Événements</a>
      <a href="{{ route('students.index') }}" class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">Étudiants</a>
    </nav>
    <div class="user-box">
      Connecté en tant que<br/><strong>{{ auth()->user()->name }}</strong>
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="logout-btn">Se déconnecter</button>
      </form>
    </div>
  </aside>

  <main>
    @yield('content')
  </main>
</div>

<script>
  // Ajoute automatiquement le jeton CSRF à tous les appels fetch (POST/PUT/PATCH/DELETE)
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
  const originalFetch = window.fetch;
  window.fetch = function (url, options = {}) {
    options.headers = {
      'X-CSRF-TOKEN': CSRF_TOKEN,
      'Accept': 'application/json',
      ...(options.headers || {}),
    };
    return originalFetch(url, options);
  };
</script>

@yield('scripts')
</body>
</html>
