<?php
/**
 * Forum Client Template
 *
 * NOTE: You shouldn't edit this file directly. Your changes will be overwritten
 * when you update Flarum. See flarum.org/docs/templates to learn how to
 * customize your forum's layout.
 *
 * Flarum's JavaScript client mounts various components into key elements in
 * this template. They are distinguished by their ID attributes:
 *
 * - #app
 * - #app-navigation
 * - #drawer
 * - #header
 * - #header-navigation
 * - #home-link
 * - #header-primary
 * - #header-secondary
 * - #content
 * - #composer
 */
?>
<div id="app" class="App">

  <div id="app-navigation" class="App-navigation"></div>

  <div id="drawer" class="App-drawer">

    <header id="header" class="App-header">
      <div id="header-navigation" class="Header-navigation"></div>
      <div class="container">
        <h1 class="Header-title">
          <a href="{{ $forum->attributes->baseUrl }}" id="home-link">
            {{ $forum->attributes->title }}
          </a>
        </h1>
        <div id="header-primary" class="Header-primary"></div>
        <div id="header-secondary" class="Header-secondary"></div>
      </div>
    </header>

  </div>

  <main class="App-content">
    <div id="content"></div>

    {!! $content !!}

    <div class="App-composer">
      <div class="container">
        <div id="composer"></div>
      </div>
    </div>
  </main>

</div>
