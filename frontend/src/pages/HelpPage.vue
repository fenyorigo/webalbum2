<template>
  <div class="page help-page">
    <header class="hero">
      <h1>{{ $t("help.title", "WebAlbum User Guide") }}</h1>
      <p>{{ $t("help.subtitle", "Overview of the normal user features in WebAlbum.") }}</p>
    </header>

    <section class="panel">
      <h2>{{ $t("help.purpose.title", "What is WebAlbum for?") }}</h2>
      <p>
        {{ $t("help.purpose.p1", "WebAlbum is for browsing and finding family memories again. The system can display images, videos, audio files, and documents.") }}
      </p>
      <ul>
        <li>{{ $t("help.purpose.item1", "Sign in to the system") }}</li>
        <li>{{ $t("help.purpose.item2", "Search using different criteria") }}</li>
        <li>{{ $t("help.purpose.item3", "Show results in list or grid view") }}</li>
        <li>{{ $t("help.purpose.item4", "Use preview, navigation, and slideshow") }}</li>
        <li>{{ $t("help.purpose.item5", "Select and download files") }}</li>
        <li>{{ $t("help.purpose.item6", "Manage favorites, notes, and proposals") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.login.title", "Sign in") }}</h2>
      <ol>
        <li>{{ $t("help.login.step1", "Enter your username.") }}</li>
        <li>{{ $t("help.login.step2", "Enter your password.") }}</li>
        <li>{{ $t("help.login.step3", 'Click the "Login" button.') }}</li>
      </ol>
      <p>{{ $t("help.login.p1", "After a successful sign-in, the system opens the Search page.") }}</p>
      <p>{{ $t("help.login.p2", "If the system asks for a password change, you must change it before you can continue.") }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.nav.title", "What does the user see?") }}</h2>
      <p>{{ $t("help.nav.p1", "The top navigation bar usually contains these pages:") }}</p>
      <ul>
        <li>{{ $t("help.nav.item1", "Search - searching") }}</li>
        <li>{{ $t("help.nav.item2", "Tags - list of tags") }}</li>
        <li>{{ $t("help.nav.item3", "Typed Tags - browse typed tags") }}</li>
        <li>{{ $t("help.nav.item4", "My Favorites - your favorites") }}</li>
        <li>{{ $t("help.nav.item5", "Saved searches - saved queries") }}</li>
        <li>{{ $t("help.nav.item6", "Help - this user guide") }}</li>
        <li>{{ $t("help.nav.item7", "My Profile - personal settings and password change") }}</li>
        <li>{{ $t("help.nav.item8", "My Proposals - your change proposals") }}</li>
        <li>{{ $t("help.nav.item9", "My Notes - your notes") }}</li>
      </ul>
      <p>{{ $t("help.nav.p2", 'The upper-right corner shows the current user and the "Logout" button.') }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.files.title", "What file types does the system handle?") }}</h2>
      <p>{{ $t("help.files.p1", 'On the Search page, the "Type" filter can narrow results to these main groups:') }}</p>
      <ul>
        <li>{{ $t("help.files.type1", "Photos") }}</li>
        <li>{{ $t("help.files.type2", "Videos") }}</li>
        <li>{{ $t("help.files.type3", "Audio") }}</li>
        <li>{{ $t("help.files.type4", "Documents") }}</li>
      </ul>
      <p>{{ $t("help.files.p2", 'The "Extension" filter currently supports these extensions:') }}</p>
      <ul>
        <li>{{ $t("help.files.ext1", "pdf, txt, doc, docx") }}</li>
        <li>{{ $t("help.files.ext2", "xls, xlsx, ppt, pptx") }}</li>
        <li>{{ $t("help.files.ext3", "mp3, m4a, flac") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.search.title", "Search") }}</h2>
      <p>{{ $t("help.search.p1", "The main work area is the Search page. A folder tree appears on the left, while the search filters and results appear in the middle.") }}</p>
      <ul>
        <li>{{ $t("help.search.item1", 'Tags - enter one or more tags with "AND" or "AND NOT" logic') }}</li>
        <li>{{ $t("help.search.item2", 'Tag match - use "All" or "Any" for the included tags') }}</li>
        <li>{{ $t("help.search.item3", "Path contains - search for text in the folder path or filename") }}</li>
        <li>{{ $t("help.search.item4", "Media ID(s) - search by exact item identifiers") }}</li>
        <li>{{ $t("help.search.item5", "Typed tag - search by typed tag") }}</li>
        <li>{{ $t("help.search.item6", 'Taken - filter by date using "After", "Before", or "Between"') }}</li>
        <li>{{ $t("help.search.item7", "Type - photo, video, audio, or document") }}</li>
        <li>{{ $t("help.search.item8", "Extension - document or audio extension") }}</li>
        <li>{{ $t("help.search.item9", "Has notes - only show items that already have notes") }}</li>
        <li>{{ $t("help.search.item10", "Only favorites - search only inside your own favorites") }}</li>
      </ul>
      <p>{{ $t("help.search.p2", 'Active filters are also shown above the results as removable chips. Click the "X" on a chip to remove that filter.') }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.typed.title", "What is a typed tag?") }}</h2>
      <p>
        {{ $t("help.typed.p1", "A typed tag is a structured tag that helps connect files that belong together, even when they are stored in different folders.") }}
      </p>
      <p>{{ $t("help.typed.p2", "You can use typed tags in two main places:") }}</p>
      <ul>
        <li>{{ $t("help.typed.item1", 'on the Search page in the "Typed tag" field') }}</li>
        <li>{{ $t("help.typed.item2", 'on the "Typed Tags" page by browsing the tree') }}</li>
      </ul>
      <p>
        {{ $t("help.typed.p3", 'If "Include descendants" is enabled, the search also covers child tags under the selected typed tag.') }}
      </p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.results.title", "Sorting and displaying results") }}</h2>
      <p>{{ $t("help.results.p1", 'Results can be sorted using the "Sort" and "Direction" fields.') }}</p>
      <ul>
        <li>{{ $t("help.results.item1", "Sort by Path") }}</li>
        <li>{{ $t("help.results.item2", "Sort by Taken date") }}</li>
        <li>{{ $t("help.results.item3", "Use ascending or descending direction") }}</li>
      </ul>
      <p>{{ $t("help.results.p2", "Results can be displayed in two ways:") }}</p>
      <ul>
        <li>{{ $t("help.results.item4", "List - table-like layout") }}</li>
        <li>{{ $t("help.results.item5", "Grid - thumbnail grid layout") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.download.title", "Selection and download") }}</h2>
      <p>
        {{ $t("help.download.p1", 'Results can be selected with checkboxes, then downloaded with the "Download selected" button as a ZIP file.') }}
      </p>
      <p>{{ $t("help.download.p2", "A single ZIP download can contain at most 20 selected items.") }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.preview.title", "Preview, navigation, slideshow") }}</h2>
      <p>{{ $t("help.preview.p1", "Clicking a result opens a preview.") }}</p>
      <ul>
        <li>{{ $t("help.preview.item1", "For images, you can move to the previous or next result") }}</li>
        <li>{{ $t("help.preview.item2", "For videos, playback and stop controls are available") }}</li>
        <li>{{ $t("help.preview.item3", "For audio files, a built-in audio player appears") }}</li>
        <li>{{ $t("help.preview.item4", "For documents, an embedded preview is shown") }}</li>
        <li>{{ $t("help.preview.item5", "The slideshow follows the order of the current result list") }}</li>
      </ul>
      <p>
        {{ $t("help.preview.p2", "The slideshow speed can be set in seconds. For videos and audio files, the system waits for playback to finish before moving on.") }}
      </p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.collab.title", "Notes and change proposals") }}</h2>
      <p>
        {{ $t("help.collab.p1", 'Every result can open its "Object" page. This is where notes and change proposals can be recorded.') }}
      </p>
      <h3>{{ $t("help.collab.notes_title", "Notes") }}</h3>
      <ul>
        <li>{{ $t("help.collab.notes_item1", "create a new note") }}</li>
        <li>{{ $t("help.collab.notes_item2", "edit your own note") }}</li>
        <li>{{ $t("help.collab.notes_item3", "delete your own note") }}</li>
      </ul>
      <h3>{{ $t("help.collab.proposals_title", "Proposals") }}</h3>
      <p>{{ $t("help.collab.p2", "A proposal can include a type and a short description.") }}</p>
      <p>{{ $t("help.collab.p3", "Typical proposal flow:") }}</p>
      <ol>
        <li>{{ $t("help.collab.step1", "the user submits a proposal") }}</li>
        <li>{{ $t("help.collab.step2", 'the proposal enters "pending" status') }}</li>
        <li>{{ $t("help.collab.step3", "later it can be approved, rejected, or cancelled") }}</li>
      </ol>
      <p>{{ $t("help.collab.p4", 'Your own proposals can be followed on "My Proposals", and your notes on "My Notes".') }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.saved.title", "Saved searches") }}</h2>
      <p>
        {{ $t("help.saved.p1", 'The current search conditions can be stored with the "Save search" button.') }}
      </p>
      <p>{{ $t("help.saved.p2", 'On the "Saved searches" page, a saved search can be:') }}</p>
      <ul>
        <li>{{ $t("help.saved.item1", "run immediately") }}</li>
        <li>{{ $t("help.saved.item2", "loaded back into the search form") }}</li>
        <li>{{ $t("help.saved.item3", "renamed") }}</li>
        <li>{{ $t("help.saved.item4", "deleted") }}</li>
      </ul>
    </section>

    <section class="panel">
      <h2>{{ $t("help.profile.title", "Personal profile and password change") }}</h2>
      <p>{{ $t("help.profile.p1", 'On the "My Profile" page, the user can change their own preferences:') }}</p>
      <ul>
        <li>{{ $t("help.profile.item1", "default view") }}</li>
        <li>{{ $t("help.profile.item2", "page size") }}</li>
        <li>{{ $t("help.profile.item3", "thumbnail size") }}</li>
        <li>{{ $t("help.profile.item4", "default sorting") }}</li>
        <li>{{ $t("help.profile.item5", "interface language") }}</li>
      </ul>
      <p>{{ $t("help.profile.p2", "The password can also be changed on this page by providing the current password.") }}</p>
    </section>

    <section class="panel">
      <h2>{{ $t("help.important.title", "Important notes") }}</h2>
      <ul>
        <li>{{ $t("help.important.item1", "Favorites and saved searches are stored separately for each user.") }}</li>
        <li>{{ $t("help.important.item2", "Saving a search stores the conditions, not the files themselves.") }}</li>
        <li>{{ $t("help.important.item3", "Results may span multiple pages, so pay attention to paging.") }}</li>
        <li>{{ $t("help.important.item4", 'You can sign out using the "Logout" button in the upper-right corner.') }}</li>
      </ul>
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

.help-page .panel h2,
.help-page .panel h3 {
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
