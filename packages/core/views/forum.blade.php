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
 * - #page
 * - #page-navigation
 * - #header
 * - #header-navigation
 * - #home-link
 * - #header-primary
 * - #header-secondary
 * - #footer
 * - #footer-primary
 * - #footer-secondary
 * - #content
 * - #composer
 */
?>
<div id="page" class="global-page">

  <div id="page-navigation"></div>

  <div class="global-drawer">

    <header id="header" class="global-header">
      <div id="header-navigation"></div>
      <div class="container">
        <h1 class="header-title">
          <a href="{{ $forum->attributes->baseUrl }}" id="home-link">
            {{ $forum->attributes->title }}
          </a>
        </h1>
        <div id="header-primary" class="header-primary"></div>
        <div id="header-secondary" class="header-secondary"></div>
      </div>
    </header>

    <footer id="footer" class="global-footer">
      <div class="container">
        <div id="footer-primary" class="footer-primary"></div>
        <div id="footer-secondary" class="footer-secondary"></div>
      </div>
    </footer>

  </div>

  <main class="global-content">
    <div id="content"></div>

    <div class="composer-container">
      <div class="container">
        <div id="composer"></div>
      </div>
    </div>
  </main>

</div>
