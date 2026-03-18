<template>
  <div class="page help-page">
    <header class="hero">
      <h1>{{ $t("help.title", "WebAlbum - Quick User Guide") }}</h1>
    </header>

    <section class="panel">
      <h2>{{ $t("help.quick_start.title", "Quick start") }}</h2>
      <p>{{ $t("help.quick_start.intro", "The easiest way to start using WebAlbum:") }}</p>
      <ol>
        <li>{{ $t("help.quick_start.step1", "Open the Tags menu") }}</li>
        <li>{{ $t("help.quick_start.step2", "Select an event") }}</li>
        <li>{{ $t("help.quick_start.step3", "Browse the images") }}</li>
      </ol>
    </section>

    <section class="panel">
      <h2>{{ $t("help.typed_tags_intro.title", "What is a typed tag?") }}</h2>
      <p>{{ $t("help.typed_tags_intro.p1", "Typed tags connect images to events or categories.") }}</p>
      <p>{{ $t("help.typed_tags_intro.p2", "They help group related images together logically, even if the files are stored in different folders.") }}</p>
      <p>{{ $t("help.typed_tags_intro.p3", "This makes it easier to browse and search family memories by event or topic, not only by folder.") }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.purpose.title", "What is WebAlbum for?") }}</h2>
      <p>{{ $t("help.purpose.p1", "WebAlbum is designed for browsing and searching family photos, videos, and documents.") }}</p>
      <p>{{ $t("help.purpose.p2", "Some of the images show identified members of the family. These images are marked with person-related tags.") }}</p>
      <p>{{ $t("help.purpose.p3", "These tags are stored directly inside the image files, so the images themselves carry this information independently of WebAlbum.") }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.tags.title", "Tags and typed tags") }}</h2>
      <p>{{ $t("help.tags.p1", "Images can also have typed tags that represent events or categories. Typed tags allow images to be logically connected even if they are located in different folders.") }}</p>
      <ul>
        <li>{{ $t("help.tags.item1", "Tags - tags related to people or general topics") }}</li>
        <li>{{ $t("help.tags.item2", "Typed tags - tags representing events or categories") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.search.title", "Search") }}</h2>
      <p>{{ $t("help.search.p1", "The easiest way to find images is by using the Search page.") }}</p>
      <ul>
        <li>{{ $t("help.search.item1", "person") }}</li>
        <li>{{ $t("help.search.item2", "tag") }}</li>
        <li>{{ $t("help.search.item3", "typed tag (event or category)") }}</li>
        <li>{{ $t("help.search.item4", "folder") }}</li>
        <li>{{ $t("help.search.item5", "date or file type (image, video, etc.)") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.image_open.title", "Opening images") }}</h2>
      <p>{{ $t("help.image_open.p1", "Clicking an image opens it in a larger view.") }}</p>
      <ul>
        <li>{{ $t("help.image_open.item1", "move to the next image") }}</li>
        <li>{{ $t("help.image_open.item2", "play a video") }}</li>
        <li>{{ $t("help.image_open.item3", "start a slideshow") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.filters.title", "Search filters") }}</h2>
      <p>{{ $t("help.filters.p1", "The active search filters are always visible above the results list. To remove a filter, click the X icon.") }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.favorites.title", "Favorites") }}</h2>
      <p>{{ $t("help.favorites.p1", "Images can be marked as favorites to make them easier to find later.") }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.notes.title", "Notes") }}</h2>
      <p>{{ $t("help.notes.p1", "You can also add notes to an image, for example if:") }}</p>
      <ul>
        <li>{{ $t("help.notes.item1", "someone is incorrectly tagged") }}</li>
        <li>{{ $t("help.notes.item2", "some information is missing") }}</li>
        <li>{{ $t("help.notes.item3", "you want to add a story or context") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.important.title", "Important") }}</h2>
      <p>{{ $t("help.important.p1", "WebAlbum does not modify the original image files.") }}</p>
      <p>{{ $t("help.important.p2", "The goal of the system is to help organize, find, and browse family memories.") }}</p>
    </section>
  </div>
</template>

<script>
export default {
  name: "HelpPage"
};
</script>

<style scoped>
.help-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.help-page .panel h2 {
  margin-top: 0;
}

.help-page .panel p:last-child,
.help-page .panel ul:last-child,
.help-page .panel ol:last-child {
  margin-bottom: 0;
}

.help-page ul,
.help-page ol {
  padding-left: 1.25rem;
}

.help-page li + li {
  margin-top: 0.35rem;
}
</style>
