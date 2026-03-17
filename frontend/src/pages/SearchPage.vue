<template>
  <div class="page">
    <div class="search-layout">
      <aside class="folders-sidebar">
        <folder-tree
          :selected-rel-path="selectedFolder ? selectedFolder.rel_path : ''"
          @select="selectFolder"
          @clear="clearFolderFilter"
        />
      </aside>
      <div class="search-main">
    <header class="hero">
      <h1>{{ $t("search.title", "Family memories") }}</h1>
      <p>{{ $t("search.subtitle", "Query your indexer DB (read-only).") }}</p>
    </header>

    <section class="panel">
      <div v-if="loadedSearchName" class="loaded-indicator">
        <span>{{ $t("search.loaded", "Loaded") }}: {{ loadedSearchName }}</span>
        <span v-if="isModified" class="pill">{{ $t("common.modified", "Modified") }}</span>
        <button v-if="isModified" class="inline" type="button" @click="resetToLoaded">
          {{ $t("ui.reset_loaded", "Reset to loaded") }}
        </button>
      </div>
      <div class="row">
        <label class="tags">
          {{ $t("search.tags", "Tags") }}
          <div class="tag-rows">
            <div v-for="(tag, idx) in form.tags" :key="idx" class="tag-row">
              <select v-model="form.tags[idx].mode">
                <option value="include">{{ $t("search.logic.and", "AND") }}</option>
                <option value="exclude">{{ $t("search.logic.and_not", "AND NOT") }}</option>
              </select>
              <input
                v-model="form.tags[idx].value"
                type="text"
                :placeholder="$t('search.tag_placeholder', 'Tag')"
                @focus="setActiveTag(idx)"
                @input="onTagInput(idx)"
                @keydown.enter.prevent="runSearch(true)"
              />
              <button type="button" class="tag-remove" @click="clearTagRow(idx)">✕</button>
            </div>
          </div>
          <button type="button" class="tag-add" @click="addTagRow">+ {{ $t("search.add_tag", "Add tag") }}</button>
          <div v-if="activeTagIndex !== null && suggestions.length" class="suggestions">
            <button
              v-for="item in suggestions"
              :key="item.tag"
              type="button"
              class="suggestion"
              @click="applySuggestion(item.tag)"
            >
              <span class="name">{{ item.tag }}</span>
              <span class="count">{{ item.cnt }}</span>
            </button>
          </div>
        </label>
        <label>
          {{ $t("search.tag_match", "Tag match") }}
          <select v-model="form.tagMode">
            <option value="ALL">{{ $t("search.tag_match.all", "All") }}</option>
            <option value="ANY">{{ $t("search.tag_match.any", "Any") }}</option>
          </select>
        </label>
        <label>
          {{ $t("search.path_contains", "Path contains") }}
          <input v-model.trim="form.path" placeholder="/Trips/" />
        </label>
        <label>
          {{ $t("search.media_ids", "Media ID(s)") }}
          <input v-model.trim="form.mediaIds" placeholder="4490, 3579, 1107" />
        </label>
        <label class="tags">
          {{ $t("search.semantic_tag", "Typed tag") }}
          <input
            v-model.trim="searchSemanticInput"
            type="text"
            :placeholder="$t('search.semantic_tag_placeholder', 'Search typed tag...')"
            @input="onSearchSemanticInput"
          />
          <div v-if="searchSemanticSuggestions.length" class="suggestions">
            <button
              v-for="item in searchSemanticSuggestions"
              :key="`search-semantic:${item.id}`"
              type="button"
              class="suggestion"
              @click="applySearchSemanticSuggestion(item)"
            >
              <span class="name">{{ item.name }}</span>
              <span class="count">{{ semanticTypeLabel(item.tag_type) }}</span>
            </button>
          </div>
          <p v-if="searchSemanticSelected" class="muted">
            {{ $t("search.semantic_tag_selected", { name: searchSemanticSelected.name }, "Selected typed tag: {name}") }}
            <button type="button" class="inline" @click="clearSearchSemanticTag">{{ $t("ui.clear", "Clear") }}</button>
          </p>
          <label v-if="searchSemanticSelected" class="checkbox">
            <input type="checkbox" v-model="searchSemanticIncludeDescendants" />
            {{ $t("search.semantic_tag_descendants", "Include descendants") }}
          </label>
        </label>
      </div>
      <div class="row">
        <label>
          {{ $t("search.taken", "Taken") }}
          <select v-model="form.dateOp">
            <option value="after">{{ $t("search.taken.after", "After") }}</option>
            <option value="before">{{ $t("search.taken.before", "Before") }}</option>
            <option value="between">{{ $t("search.taken.between", "Between") }}</option>
          </select>
        </label>
        <label v-if="form.dateOp !== 'between'">
          {{ $t("search.date", "Date") }}
          <input v-model.trim="form.date" type="text" placeholder="YYYY-MM-DD" />
        </label>
        <label v-else>
          {{ $t("search.start", "Start") }}
          <input v-model.trim="form.start" type="text" placeholder="YYYY-MM-DD" />
        </label>
        <label v-if="form.dateOp === 'between'">
          {{ $t("search.end", "End") }}
          <input v-model.trim="form.end" type="text" placeholder="YYYY-MM-DD" />
        </label>
      </div>
      <div class="row">
        <label>
          {{ $t("search.sort", "Sort") }}
          <select v-model="form.sortField">
            <option value="path">{{ $t("search.sort.path", "Path") }}</option>
            <option value="taken">{{ $t("search.sort.taken", "Taken") }}</option>
          </select>
        </label>
        <label>
          {{ $t("search.direction", "Direction") }}
          <select v-model="form.sortDir">
            <option value="asc">{{ sortDirLabel("asc") }}</option>
            <option value="desc">{{ sortDirLabel("desc") }}</option>
          </select>
        </label>
        <label>
          {{ $t("search.type", "Type") }}
          <select v-model="form.type">
            <option value="">{{ $t("search.tag_match.any", "Any") }}</option>
            <option value="image">{{ $t("search.type.photos", "Photos") }}</option>
            <option value="video">{{ $t("search.type.videos", "Videos") }}</option>
            <option value="audio">{{ $t("search.type.audio", "Audio") }}</option>
            <option value="doc">{{ $t("search.type.documents", "Documents") }}</option>
          </select>
        </label>
        <label>
          <span>{{ $t("search.has_notes", "Has notes") }}</span>
          <input v-model="form.hasNotes" type="checkbox" />
        </label>
        <label>
          {{ $t("search.extension", "Extension") }}
          <select v-model="form.ext">
            <option value="">{{ $t("search.tag_match.any", "Any") }}</option>
            <option value="pdf">PDF</option>
            <option value="txt">TXT</option>
            <option value="doc">DOC</option>
            <option value="docx">DOCX</option>
            <option value="xls">XLS</option>
            <option value="xlsx">XLSX</option>
            <option value="ppt">PPT</option>
            <option value="pptx">PPTX</option>
            <option value="mp3">MP3</option>
            <option value="m4a">M4A</option>
            <option value="flac">FLAC</option>
          </select>
        </label>
        <label>
          <span>{{ $t("search.only_favorites", "Only favorites") }}</span>
          <input v-model="form.onlyFavorites" type="checkbox" :disabled="!canFavorite" />
        </label>
        <label>
          {{ $t("search.limit", "Limit") }}
          <input v-model.number="form.limit" type="number" min="1" max="1000" />
        </label>
        <label>
          {{ $t("search.view", "View") }}
          <select v-model="viewMode">
            <option value="list">{{ $t("common.view_mode.list", "List") }}</option>
            <option value="grid">{{ $t("common.view_mode.grid", "Grid") }}</option>
          </select>
        </label>
      </div>
      <div class="row actions">
        <button @click="runSearch(true)" :disabled="loading">{{ $t("search.button", "Search") }}</button>
        <span v-if="selectedFolder" class="pill folder-pill" :title="selectedFolder.rel_path">{{ $t("search.folder", "Folder") }}: {{ selectedFolder.rel_path }}</span>
        <label v-if="selectedFolder" class="checkbox">
          <input type="checkbox" v-model="form.folderRecursive" />
          {{ $t("search.folder_recursive", "Recursive") }}
        </label>
        <button v-if="selectedFolder" class="clear" type="button" @click="clearFolderFilter">{{ $t("search.folder_clear", "Clear folder filter") }}</button>
        <button @click="openSaveModal" :disabled="loading">{{ $t("search.save_search", "Save search") }}</button>
        <button v-if="isAdmin" @click="openTagHistoryModal" :disabled="loading">{{ $t("search.tag_changes", "Tag changes") }}</button>
        <button @click="clearCriteria" :disabled="loading">{{ $t("search.clear_criteria", "Clear search criteria") }}</button>
        <label class="checkbox">
          <input type="checkbox" v-model="debug" />
          {{ $t("search.debug_sql", "Debug SQL") }}
        </label>
      </div>
      <p v-if="error" class="error">{{ error }}</p>
    </section>

    <section class="results">
      <div class="meta">
        <span v-if="loading">{{ $t("common.loading", "Loading...") }}</span>
        <span v-else-if="total === null">{{ $t("search.results_empty", "Results: —") }}</span>
        <span v-else>{{ resultsSummary }}</span>
        <span v-if="savedBanner" class="pill">{{ savedBanner }}</span>
      </div>
      <div class="view-toggle">
        <button
          type="button"
          :class="{ active: viewMode === 'list' }"
          @click="viewMode = 'list'"
        >
          {{ $t("common.view_mode.list", "List") }}
        </button>
        <button
          type="button"
          :class="{ active: viewMode === 'grid' }"
          @click="viewMode = 'grid'"
        >
          {{ $t("common.view_mode.grid", "Grid") }}
        </button>
      </div>
      <div class="pager" v-if="total !== null && total > (form.limit || 50)">
        <button :disabled="page === 1 || loading" @click="prevPage">{{ $t("ui.previous", "Previous") }}</button>
        <span>{{ $t("audit.page_of", { x: page, y: totalPages }, "Page {x} of {y}") }}</span>
        <input
          v-model.number="pageInput"
          type="number"
          min="1"
          :max="totalPages"
          :placeholder="$t('ui.go', 'Go')"
        />
        <button :disabled="loading" @click="jumpToPage">{{ $t("ui.go", "Go") }}</button>
        <button :disabled="page >= totalPages || loading" @click="nextPage">{{ $t("ui.next", "Next") }}</button>
        <button
          class="download"
          :disabled="loading || selectedIds.length === 0 || selectedIds.length > 20"
          @click="downloadSelected"
        >
          {{ $t("search.download_selected", "Download selected") }} ({{ selectedIds.length }})
        </button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading || batchTagOpenDisabled"
          @click="openBatchTagModal"
        >
          {{ $t("search.batch_tag_edit", "Batch tag edit") }} ({{ selectedIds.length || total || 0 }})
        </button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading || batchTagOpenDisabled"
          @click="openSemanticAssignModal"
        >
          {{ $t("semantic_tags.assign", "Assign typed tag") }} ({{ selectedIds.length || total || 0 }})
        </button>
        <span class="note">{{ $t("search.download_limit_note", "Max 20 files per ZIP") }}</span>
        <button
          v-if="selectedIds.length"
          class="clear"
          type="button"
          @click="clearSelection"
        >
          {{ $t("search.unselect_all", "Unselect all") }}
        </button>
      </div>
      <results-list
        v-if="viewMode === 'list'"
        :items="results"
        :offset="offset"
        :selected-ids="selectedIds"
        :can-favorite="canFavorite"
        :file-url="fileUrl"
        :thumb-url="thumbUrl"
        :format-ts="formatTs"
        :copy-link="copyLink"
        :file-name="fileName"
        @open="openViewer"
        @open-object="openObjectPage"
        @toggle-favorite="toggleFavorite"
        @update:selected-ids="selectedIds = $event"
      />
      <results-grid
        v-else
        :items="results"
        :offset="offset"
        :selected-ids="selectedIds"
        :can-favorite="canFavorite"
        :can-trash="isAdmin"
        :file-url="fileUrl"
        :thumb-url="thumbUrl"
        :format-ts="formatTs"
        :copy-link="copyLink"
        :file-name="fileName"
        @open="openViewer"
        @open-object="openObjectPage"
        @toggle-favorite="toggleFavorite"
        @update:selected-ids="selectedIds = $event"
        @request-trash="requestTrash"
      />
      <div
        class="pager"
        v-if="total !== null && total <= (form.limit || 50)"
      >
        <button
          class="download"
          :disabled="loading || selectedIds.length === 0 || selectedIds.length > 20"
          @click="downloadSelected"
        >
          {{ $t("search.download_selected", "Download selected") }} ({{ selectedIds.length }})
        </button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading || batchTagOpenDisabled"
          @click="openBatchTagModal"
        >
          {{ $t("search.batch_tag_edit", "Batch tag edit") }} ({{ selectedIds.length || total || 0 }})
        </button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading || batchTagOpenDisabled"
          @click="openSemanticAssignModal"
        >
          {{ $t("semantic_tags.assign", "Assign typed tag") }} ({{ selectedIds.length || total || 0 }})
        </button>
        <span class="note">Max 20 files per ZIP</span>
        <button
          v-if="selectedIds.length"
          class="clear"
          type="button"
          @click="clearSelection"
        >
          {{ $t("search.unselect_all", "Unselect all") }}
        </button>
      </div>
      <div class="pager" v-if="total !== null && total > (form.limit || 50)">
        <button :disabled="page === 1 || loading" @click="prevPage">{{ $t("ui.previous", "Previous") }}</button>
        <span>{{ $t("audit.page_of", { x: page, y: totalPages }, "Page {x} of {y}") }}</span>
        <input
          v-model.number="pageInput"
          type="number"
          min="1"
          :max="totalPages"
          :placeholder="$t('ui.go', 'Go')"
        />
        <button :disabled="loading" @click="jumpToPage">{{ $t("ui.go", "Go") }}</button>
        <button :disabled="page >= totalPages || loading" @click="nextPage">{{ $t("ui.next", "Next") }}</button>
        <button
          class="download"
          :disabled="loading || selectedIds.length === 0 || selectedIds.length > 20"
          @click="downloadSelected"
        >
          {{ $t("search.download_selected", "Download selected") }} ({{ selectedIds.length }})
        </button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading || batchTagOpenDisabled"
          @click="openBatchTagModal"
        >
          {{ $t("search.batch_tag_edit", "Batch tag edit") }} ({{ selectedIds.length || total || 0 }})
        </button>
        <button
          v-if="isAdmin"
          class="inline"
          :disabled="loading || batchTagOpenDisabled"
          @click="openSemanticAssignModal"
        >
          {{ $t("semantic_tags.assign", "Assign typed tag") }} ({{ selectedIds.length || total || 0 }})
        </button>
        <span class="note">{{ $t("search.download_limit_note", "Max 20 files per ZIP") }}</span>
      </div>
      <pre v-if="debugInfo" class="debug">{{ debugInfo }}</pre>
    </section>
      </div>
    </div>
    <image-viewer
      :results="viewerResults"
      :start-id="viewerStartId"
      :is-open="viewerOpen"
      :file-url="fileUrl"
      :current-user="currentUser"
      :slideshow-active="slideshowActive"
      :slideshow-seconds="slideshowSeconds"
      @close="closeViewer"
      @trashed="onItemTrashed"
      @open-asset="openAssetFromImageViewer"
      @open-video="openVideoFromImageViewer"
      @open-object="openObjectPage"
      @rotated="onMediaRotated"
      @slideshow-start="startSlideshow"
      @slideshow-stop="stopSlideshow"
      @slideshow-seconds-change="updateSlideshowSeconds"
      @slideshow-finished="finishSlideshow"
    />
    <video-viewer
      :results="viewerResults"
      :start-id="videoViewerStartId"
      :is-open="videoViewerOpen"
      :video-url="videoUrl"
      :current-user="currentUser"
      :slideshow-active="slideshowActive"
      :slideshow-seconds="slideshowSeconds"
      @close="closeVideoViewer"
      @trashed="onItemTrashed"
      @open-asset="openAssetFromVideoViewer"
      @open-image="openImageFromVideoViewer"
      @open-object="openObjectPage"
      @rotated="onMediaRotated"
      @slideshow-start="startSlideshow"
      @slideshow-stop="stopSlideshow"
      @slideshow-seconds-change="updateSlideshowSeconds"
      @slideshow-finished="finishSlideshow"
    />
    <div v-if="assetViewerOpen" class="modal-backdrop" @click.self="closeAssetViewer">
      <div class="modal asset-modal">
        <div class="modal-header">
          <h3>{{ assetViewerRow && fileName(assetViewerRow.path) }}</h3>
          <button class="inline" type="button" @click="closeAssetViewer">{{ $t("ui.close", "Close") }}</button>
        </div>
        <p class="muted" :title="assetViewerRow && assetViewerRow.path">{{ assetViewerRow && assetViewerRow.path }}</p>
        <div v-if="assetViewerRow && assetViewerRow.type === 'audio'" class="asset-body">
          <audio
            ref="assetAudio"
            controls
            :src="assetFileUrl(assetViewerRow)"
            @ended="onAssetAudioEnded"
          ></audio>
        </div>
        <div v-else class="asset-body doc-body">
          <iframe :src="assetViewUrl(assetViewerRow)" :title="$t('common.preview', 'Preview')"></iframe>
        </div>
        <div class="modal-actions">
          <label class="slideshow-control">
            <span>{{ $t("search.asset_seconds", "Sec") }}</span>
            <input v-model.number="slideshowSeconds" type="number" min="1" max="3600" />
          </label>
          <button class="inline" type="button" @click="toggleSlideshow">
            {{ slideshowActive ? $t("viewer.end_slideshow", "End slideshow") : $t("viewer.start_slideshow", "Start slideshow") }}
          </button>
          <button class="inline" type="button" @click="assetPrev" :disabled="assetViewerIndex <= 0">{{ $t("ui.previous", "Previous") }}</button>
          <button class="inline" type="button" @click="assetNext" :disabled="assetViewerIndex < 0 || assetViewerIndex >= results.length - 1">{{ $t("ui.next", "Next") }}</button>
          <button class="inline" type="button" @click="openAssetOriginal">{{ $t("common.download_original", "Download original") }}</button>
          <button class="inline" type="button" @click="openObjectPage(assetViewerRow)">{{ $t("search.object_notes", "Object notes") }}</button>
        </div>
        <p v-if="assetViewerError" class="error">{{ assetViewerError }}</p>
      </div>
    </div>
    <div v-if="saveOpen" class="modal-backdrop" @click.self="closeSaveModal">
      <div class="modal">
        <h3>{{ $t("search.save_modal_title", "Save search") }}</h3>
        <label>
          {{ $t("common.name", "Name") }}
          <input v-model.trim="saveName" type="text" />
        </label>
        <div class="modal-actions">
          <button class="inline" @click="submitSave(false)" :disabled="loading">{{ $t("ui.save", "Save") }}</button>
          <button class="inline" @click="closeSaveModal" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
        <p v-if="saveError" class="error">{{ saveError }}</p>
      </div>
    </div>
    <div v-if="batchTagOpen && !batchTagPreviewHidden" class="modal-backdrop" @click.self="closeBatchTagModal">
      <div class="modal batch-tag-modal">
        <div class="modal-header">
          <h3>{{ $t("search.batch_tag_edit_title", "Batch tag edit") }}</h3>
          <button class="inline" type="button" @click="closeBatchTagModal">{{ $t("ui.close", "Close") }}</button>
        </div>
        <p class="muted">
          {{ $t("search.batch_selected_count", { count: selectedIds.length }, "Selected: {count}.") }}
          {{ $t("search.batch_total_count", { count: total || 0 }, "Matching results: {count}.") }}
          {{ $t("search.batch_eligible_count", { count: batchTagEligibleCount }, "Eligible media: {count}.") }}
        </p>
        <div class="batch-tag-layout">
          <section class="batch-tag-controls">
            <label>
              {{ $t("search.batch_scope", "Apply to") }}
              <select v-model="batchTagScope">
                <option value="selected">{{ $t("search.batch_scope_selected", "Selected items") }}</option>
                <option value="all_results">{{ $t("search.batch_scope_all_results", "All search results") }}</option>
              </select>
            </label>
            <label>
              {{ $t("viewer.add_tag_label", "Add tag") }}
              <input
                v-model.trim="batchTagAddInput"
                type="text"
                :placeholder="$t('search.batch_add_tag_placeholder', 'Optional tag to add')"
                @input="onBatchTagInput"
              />
            </label>
            <div v-if="batchTagSuggestions.length" class="suggestions batch-tag-suggestions">
              <button
                v-for="item in batchTagSuggestions"
                :key="item.tag"
                type="button"
                class="suggestion"
                @click="applyBatchTagSuggestion(item.tag)"
              >
                <span class="name">{{ item.tag }}</span>
                <span class="count">{{ item.cnt }}</span>
              </button>
            </div>
            <div v-if="isAdmin && normalizeBatchTag(batchTagAddInput)" class="modal-actions">
              <button class="inline" type="button" @click="openBatchSemanticTagCreate">
                {{ $t("semantic_tags.create_and_assign", "Create typed tag and assign") }}
              </button>
            </div>
            <div v-if="batchTagCommonTags.length" class="batch-tag-common">
              <span class="batch-tag-label">{{ $t("search.batch_common_tags", "Common tags") }}</span>
              <div class="tag-chip-list">
                <span v-for="tag in batchTagCommonTags" :key="`common:${tag}`" class="tag-pill">
                  {{ tag }}
                </span>
              </div>
            </div>
            <div class="batch-tag-remove">
              <span class="batch-tag-label">{{ $t("search.batch_remove_tags", "Tags to remove") }}</span>
              <p v-if="!batchTagAvailableRemoveTags.length" class="muted">{{ $t("search.batch_no_removable_tags", "No removable tags found on the selection.") }}</p>
              <label
                v-for="item in batchTagAvailableRemoveTags"
                :key="`remove:${item.tag}`"
                class="batch-tag-check"
              >
                <input
                  type="checkbox"
                  :value="item.tag"
                  :checked="batchTagRemoveTags.includes(item.tag)"
                  @change="toggleBatchRemoveTag(item.tag, $event.target.checked)"
                />
                <span>{{ item.tag }}</span>
                <span class="muted">({{ item.count }})</span>
              </label>
            </div>
            <p v-if="batchTagConflict" class="error">{{ $t("search.batch_tag_conflict", "The same tag cannot be removed and added.") }}</p>
            <p v-if="batchTagError" class="error">{{ batchTagError }}</p>
            <p v-if="batchTagSummary" class="muted">
              {{ $t("search.batch_summary", { queued: batchTagSummary.queued_count, skipped: batchTagSummary.skipped_count, failed: batchTagSummary.failure_count }, "Queued {queued}, skipped {skipped}, failed {failed}.") }}
            </p>
            <div class="modal-actions">
              <button
                class="inline"
                type="button"
                :disabled="batchTagSubmitting || !batchTagHasChangeRequest || batchTagConflict"
                @click="submitBatchTagEdit"
              >
                {{ $t("search.batch_queue_edit", "Queue batch edit") }}
              </button>
              <button class="inline" type="button" :disabled="batchTagSubmitting" @click="closeBatchTagModal">
                {{ $t("ui.cancel", "Cancel") }}
              </button>
            </div>
          </section>
          <section class="batch-tag-items">
            <div v-if="batchTagLoading" class="muted">{{ $t("search.batch_loading_items", "Loading selected items...") }}</div>
            <div v-else-if="!batchTagItems.length" class="muted">{{ $t("search.batch_no_items", "No selected items.") }}</div>
            <article
              v-for="item in batchTagItems"
              :key="`batch-item:${item.id}`"
              class="batch-tag-item"
            >
              <div class="batch-tag-thumb">
                <img
                  v-if="item.status === 'ok'"
                  :src="thumbUrl(item)"
                  :alt="fileName(item.rel_path)"
                  loading="lazy"
                  class="thumb-img loaded"
                />
                <span v-else class="thumb-placeholder">{{ item.status === "unsupported" ? "?" : "!" }}</span>
              </div>
              <div class="batch-tag-meta">
                <div class="batch-tag-path" :title="item.rel_path || ''">
                  {{ item.rel_path || `ID ${item.id}` }}
                </div>
                <div class="batch-tag-status" :class="`status-${item.status}`">
                  {{ item.status }}
                  <span v-if="item.type">({{ item.type }})</span>
                  <span v-if="item.error"> - {{ item.error }}</span>
                </div>
                <div v-if="item.tags && item.tags.length" class="tag-chip-list">
                  <span v-for="tag in item.tags" :key="`${item.id}:${tag}`" class="tag-pill">
                    {{ tag }}
                  </span>
                </div>
                <div v-else class="muted">{{ $t("search.batch_no_current_tags", "No current tags") }}</div>
              </div>
              <div class="batch-tag-actions">
                <button
                  class="inline"
                  type="button"
                  :disabled="item.status !== 'ok'"
                  @click="previewBatchItem(item)"
                >
                  {{ $t("ui.preview", "Preview") }}
                </button>
                <button
                  class="inline"
                  type="button"
                  :disabled="item.status !== 'ok'"
                  @click="openObjectPage(item)"
                >
                  {{ $t("common.object", "Object") }}
                </button>
              </div>
            </article>
          </section>
        </div>
      </div>
    </div>
    <semantic-tag-create-modal
      :is-open="batchSemanticTagCreateOpen"
      :initial-name="normalizeBatchTag(batchTagAddInput)"
      @close="batchSemanticTagCreateOpen = false"
      @created="onBatchSemanticTagCreated"
    />
    <div v-if="semanticAssignOpen" class="modal-backdrop" @click.self="closeSemanticAssignModal">
      <div class="modal batch-tag-modal">
        <div class="modal-header">
          <h3>{{ $t("semantic_tags.assign_title", "Assign typed tag") }}</h3>
          <button class="inline" type="button" @click="closeSemanticAssignModal">{{ $t("ui.close", "Close") }}</button>
        </div>
        <div class="batch-tag-layout">
          <section class="batch-tag-controls">
            <label>
              {{ $t("semantic_tags.assign_scope", "Apply to") }}
              <select v-model="semanticAssignScope" @change="refreshSemanticAssignPreview">
                <option value="selected">{{ $t("semantic_tags.assign_selected", "Selected items") }}</option>
                <option value="all_results">{{ $t("semantic_tags.assign_all_results", "All search results") }}</option>
              </select>
            </label>
            <label>
              {{ $t("semantic_tags.assign_tag", "Typed tag") }}
              <input
                v-model.trim="semanticAssignInput"
                type="text"
                :placeholder="$t('semantic_tags.assign_placeholder', 'Search typed tag...')"
                @input="onSemanticAssignInput"
              />
            </label>
            <div v-if="semanticAssignSuggestions.length" class="suggestions batch-tag-suggestions">
              <button
                v-for="item in semanticAssignSuggestions"
                :key="`sem:${item.id}`"
                type="button"
                class="suggestion"
                @click="selectSemanticAssignSuggestion(item)"
              >
                <span class="name">{{ item.name }}</span>
                <span class="count">{{ semanticTypeLabel(item.tag_type) }}</span>
              </button>
            </div>
            <div v-if="normalizeBatchTag(semanticAssignInput) && !semanticAssignSelected" class="modal-actions">
              <button class="inline" type="button" @click="openSemanticAssignCreate">
                {{ $t("semantic_tags.create_and_assign", "Create typed tag and assign") }}
              </button>
            </div>
            <p v-if="semanticAssignSelected" class="muted">
              {{ $t("semantic_tags.assign_selected_tag", { name: semanticAssignSelected.name }, "Selected typed tag: {name}") }}
            </p>
            <p class="muted">{{ $t("semantic_tags.assign_count", { count: semanticAssignCount }, "Matching items: {count}.") }}</p>
            <p v-if="semanticAssignError" class="error">{{ semanticAssignError }}</p>
            <div class="modal-actions">
              <button class="inline" type="button" :disabled="semanticAssignSubmitting" @click="refreshSemanticAssignPreview">
                {{ $t("ui.refresh", "Refresh") }}
              </button>
              <button class="inline" type="button" :disabled="semanticAssignSubmitting || !semanticAssignSelected" @click="submitSemanticAssign">
                {{ $t("ui.apply", "Apply") }}
              </button>
              <button class="inline" type="button" :disabled="semanticAssignSubmitting" @click="closeSemanticAssignModal">
                {{ $t("ui.cancel", "Cancel") }}
              </button>
            </div>
          </section>
          <section class="batch-tag-items">
            <div v-if="semanticAssignLoading" class="muted">{{ $t("common.loading", "Loading...") }}</div>
            <div v-else-if="!semanticAssignItems.length" class="muted">{{ $t("search.batch_no_items", "No selected items.") }}</div>
            <article
              v-for="item in semanticAssignItems"
              :key="`semantic-assign:${item.entity_type}:${item.rel_path}`"
              class="batch-tag-item"
            >
              <div class="batch-tag-thumb">
                <img
                  v-if="item.entity_type === 'media' || item.type === 'doc'"
                  :src="item.entity_type === 'media' ? thumbUrl({ id: item.source_id, type: item.type }) : thumbUrl({ id: -item.source_id, asset_id: item.source_id, type: item.type, entity: 'asset' })"
                  :alt="fileName(item.path || item.rel_path)"
                  loading="lazy"
                  class="thumb-img loaded"
                />
                <span v-else class="thumb-placeholder">{{ item.type === 'audio' ? '♪' : '?' }}</span>
              </div>
              <div class="batch-tag-meta">
                <div class="batch-tag-path" :title="item.rel_path">{{ item.rel_path }}</div>
                <div class="batch-tag-status">{{ item.entity_type }} <span v-if="item.type">({{ item.type }})</span></div>
              </div>
            </article>
          </section>
        </div>
      </div>
    </div>
    <semantic-tag-create-modal
      :is-open="semanticAssignCreateOpen"
      :initial-name="normalizeBatchTag(semanticAssignInput)"
      @close="semanticAssignCreateOpen = false"
      @created="onSemanticAssignCreated"
    />
    <div v-if="replaceOpen" class="modal-backdrop" @click.self="closeReplaceModal">
      <div class="modal">
        <h3>{{ $t("search.replace_saved_title", "Replace saved search?") }}</h3>
        <p>{{ $t("search.replace_saved_body", "A saved search with this name already exists. Replace it?") }}</p>
        <div class="modal-actions">
          <button class="inline" @click="submitSave(true)" :disabled="loading">{{ $t("common.replace", "Replace") }}</button>
          <button class="inline" @click="closeReplaceModal" :disabled="loading">{{ $t("ui.cancel", "Cancel") }}</button>
        </div>
      </div>
    </div>
    <div v-if="historyOpen && !historyPreviewHidden" class="modal-backdrop" @click.self="closeTagHistoryModal">
      <div class="modal batch-tag-modal">
        <div class="modal-header">
          <h3>{{ $t("history.title", "Tag changes") }}</h3>
          <button class="inline" type="button" @click="closeTagHistoryModal">{{ $t("ui.close", "Close") }}</button>
        </div>
        <div class="row">
          <label>
            {{ $t("history.media_ids", "Media ID(s)") }}
            <input v-model.trim="historyIds" type="text" :placeholder="$t('search.history_filter_placeholder', 'Optional filter: 4490, 3579')" />
          </label>
          <label>
            {{ $t("history.limit", "Limit") }}
            <input v-model.number="historyLimit" type="number" min="1" max="500" />
          </label>
          <div class="modal-actions">
            <button class="inline" type="button" :disabled="historyLoading" @click="fetchTagHistory(true)">{{ $t("history.load", "Load") }}</button>
          </div>
        </div>
        <p v-if="historyError" class="error">{{ historyError }}</p>
        <p v-else class="muted">{{ $t("history.showing_count", { shown: historyItems.length, total: historyTotal }, "Showing {shown} of {total} tag edits.") }}</p>
        <div class="batch-tag-items">
          <article v-for="item in historyItems" :key="`history:${item.id}`" class="batch-tag-item">
            <div class="batch-tag-thumb">
              <img
                v-if="item.current_file_id && item.current_type"
                :src="thumbUrl({ id: item.current_file_id, type: item.current_type })"
                :alt="fileName(item.rel_path)"
                loading="lazy"
                class="thumb-img loaded"
              />
              <span v-else class="thumb-placeholder">?</span>
            </div>
            <div class="batch-tag-meta">
              <div class="batch-tag-path" :title="item.rel_path">{{ item.rel_path }}</div>
              <div class="batch-tag-status">
                Edit #{{ item.id }}
                <span v-if="item.batch_id"> | Batch {{ item.batch_id }}</span>
                <span v-if="item.current_file_id"> | File {{ item.current_file_id }}</span>
                <span> | {{ item.status }}</span>
              </div>
              <div class="muted">
                {{ item.action_type }} by {{ item.created_by_username || item.created_by_user_id || "?" }}
                {{ $t("history.at", "at") }} {{ formatDateTime(item.created_at) }}
              </div>
              <div class="tag-change-row">
                <span class="batch-tag-label">{{ $t("search.history.old", "Old") }}</span>
                <div class="tag-chip-list">
                  <span v-for="tag in item.old_tags" :key="`old:${item.id}:${tag}`" class="tag-pill">{{ tag }}</span>
                  <span v-if="!item.old_tags.length" class="muted">{{ $t("common.none", "none") }}</span>
                </div>
              </div>
              <div class="tag-change-row">
                <span class="batch-tag-label">{{ $t("search.history.new", "New") }}</span>
                <div class="tag-chip-list">
                  <span v-for="tag in item.new_tags" :key="`new:${item.id}:${tag}`" class="tag-pill">{{ tag }}</span>
                  <span v-if="!item.new_tags.length" class="muted">{{ $t("common.none", "none") }}</span>
                </div>
              </div>
              <p v-if="item.last_error" class="error">{{ item.last_error }}</p>
            </div>
            <div class="batch-tag-actions">
              <button
                class="inline"
                type="button"
                :disabled="!item.current_file_id"
                @click="previewHistoryItem(item)"
              >
                {{ $t("history.preview", "Preview") }}
              </button>
              <button
                class="inline"
                type="button"
                :disabled="!item.current_file_id || historyRestoreBusy[item.id]"
                @click="restoreHistoryItem(item)"
              >
                {{ $t("history.restore", "Restore original") }}
              </button>
            </div>
          </article>
        </div>
      </div>
    </div>
    <div v-if="toast" class="toast">{{ toast }}</div>
  </div>
</template>

<script>
import ResultsGrid from "../components/ResultsGrid.vue";
import ResultsList from "../components/ResultsList.vue";
import ImageViewer from "../components/ImageViewer.vue";
import VideoViewer from "../components/VideoViewer.vue";
import FolderTree from "../components/FolderTree.vue";
import SemanticTagCreateModal from "../components/SemanticTagCreateModal.vue";
import { apiErrorMessage } from "../api-errors";

export default {
  name: "SearchPage",
  components: { ResultsGrid, ResultsList, ImageViewer, VideoViewer, FolderTree, SemanticTagCreateModal },
  data() {
    return {
      loading: false,
      error: "",
      debug: false,
      debugInfo: "",
      results: [],
      total: null,
      offset: 0,
      limit: 50,
      form: {
        tags: [{ value: "", mode: "include" }],
        tagMode: "ALL",
        path: "",
        mediaIds: "",
        dateOp: "after",
        date: "",
        start: "",
        end: "",
        type: "",
        ext: "",
        onlyFavorites: false,
        hasNotes: false,
        folderRecursive: false,
        sortField: "path",
        sortDir: "asc",
        limit: 50
      },
      page: 1,
      pageInput: null,
      activeTagIndex: null,
      suggestions: [],
      suggestTimer: null,
      selectedIds: [],
      toast: "",
      viewMode: "list",
      viewerOpen: false,
      viewerStartId: 0,
      videoViewerOpen: false,
      videoViewerStartId: 0,
      currentUser: null,
      saveOpen: false,
      replaceOpen: false,
      saveName: "",
      saveError: "",
      savedBanner: "",
      suspendAuto: false,
      loadedSearchId: null,
      loadedSearchName: "",
      loadedQuery: null,
      loadedSnapshot: "",
      selectedFolder: null,
      searchSemanticInput: "",
      searchSemanticSelected: null,
      searchSemanticSuggestions: [],
      searchSemanticSuggestTimer: null,
      searchSemanticIncludeDescendants: false,
      assetViewerOpen: false,
      assetViewerRow: null,
      assetViewerError: "",
      mediaCacheBust: {},
      slideshowActive: false,
      slideshowSeconds: 5,
      slideshowTimer: null,
      batchTagOpen: false,
      batchTagLoading: false,
      batchTagSubmitting: false,
      batchTagError: "",
      batchTagItems: [],
      batchTagAvailableRemoveTags: [],
      batchTagCommonTags: [],
      batchTagRemoveTags: [],
      batchTagAddInput: "",
      batchTagScope: "selected",
      batchTagSuggestions: [],
      batchTagSuggestTimer: null,
      batchTagSummary: null,
      batchTagPreviewHidden: false,
      batchSemanticTagCreateOpen: false,
      semanticAssignOpen: false,
      semanticAssignLoading: false,
      semanticAssignSubmitting: false,
      semanticAssignError: "",
      semanticAssignScope: "selected",
      semanticAssignInput: "",
      semanticAssignSelected: null,
      semanticAssignSuggestions: [],
      semanticAssignSuggestTimer: null,
      semanticAssignItems: [],
      semanticAssignCount: 0,
      semanticAssignCreateOpen: false,
      historyOpen: false,
      historyLoading: false,
      historyError: "",
      historyItems: [],
      historyIds: "",
      historyLimit: 100,
      historyOffset: 0,
      historyTotal: 0,
      historyRestoreBusy: {},
      historyPreviewHidden: false,
      detachedPreviewRows: []
    };
  },
  mounted() {
    const prefs = window.__wa_prefs || null;
    this.applyPrefs(prefs);
    if (!prefs) {
      this.viewMode = window.matchMedia("(min-width: 1024px)").matches ? "grid" : "list";
    }
    this.currentUser = window.__wa_current_user || null;
    this.restoreFolderFilter();
    this.applyLoadFromRoute();
    window.addEventListener("wa-auth-changed", this.onUserChanged);
    window.addEventListener("wa-prefs-changed", this.onPrefsChanged);
    window.addEventListener("wa-media-thumb-refresh", this.onMediaThumbRefresh);
  },
  beforeUnmount() {
    this.clearSlideshowTimer();
    window.removeEventListener("wa-auth-changed", this.onUserChanged);
    window.removeEventListener("wa-prefs-changed", this.onPrefsChanged);
    window.removeEventListener("wa-media-thumb-refresh", this.onMediaThumbRefresh);
  },
  methods: {
    onUserChanged(event) {
      this.currentUser = event.detail || null;
    },
    updateSlideshowSeconds(value) {
      const parsed = Number(value);
      const next = Math.min(3600, Math.max(1, Number.isFinite(parsed) ? parsed : 5));
      this.slideshowSeconds = next;
      if (this.slideshowActive) {
        this.syncAssetSlideshow();
      }
    },
    startSlideshow(value) {
      this.updateSlideshowSeconds(value);
      this.slideshowActive = true;
      this.syncAssetSlideshow();
    },
    stopSlideshow() {
      this.slideshowActive = false;
      this.clearSlideshowTimer();
      this.stopAssetAudioPlayback();
    },
    finishSlideshow() {
      this.stopSlideshow();
    },
    toggleSlideshow() {
      if (this.slideshowActive) {
        this.stopSlideshow();
        return;
      }
      this.startSlideshow(this.slideshowSeconds);
    },
    clearSlideshowTimer() {
      if (this.slideshowTimer) {
        window.clearTimeout(this.slideshowTimer);
        this.slideshowTimer = null;
      }
    },
    stopAssetAudioPlayback() {
      const audio = this.$refs.assetAudio;
      if (!audio) {
        return;
      }
      audio.pause();
      try {
        audio.currentTime = 0;
      } catch (_e) {
        // ignore
      }
    },
    syncAssetSlideshow() {
      this.clearSlideshowTimer();
      if (!this.slideshowActive || !this.assetViewerOpen || !this.assetViewerRow) {
        return;
      }
      if (this.assetViewerRow.type === "audio") {
        this.$nextTick(() => {
          const audio = this.$refs.assetAudio;
          if (!audio) {
            return;
          }
          try {
            audio.currentTime = 0;
          } catch (_e) {
            // ignore
          }
          audio.play().catch(() => {});
        });
        return;
      }
      const seconds = Math.max(1, Number(this.slideshowSeconds || 5));
      this.slideshowTimer = window.setTimeout(() => {
        this.slideshowTimer = null;
        if (this.assetViewerIndex < 0 || this.assetViewerIndex >= this.results.length - 1) {
          this.finishSlideshow();
          return;
        }
        this.assetNext();
      }, seconds * 1000);
    },
    onAssetAudioEnded() {
      if (!this.slideshowActive) {
        return;
      }
      if (this.assetViewerIndex < 0 || this.assetViewerIndex >= this.results.length - 1) {
        this.finishSlideshow();
        return;
      }
      this.assetNext();
    },
    onPrefsChanged(event) {
      this.applyPrefs(event.detail || null);
    },
    onMediaThumbRefresh(event) {
      const stamp = Number(event && event.detail && event.detail.at ? event.detail.at : Date.now());
      const next = { ...this.mediaCacheBust };
      for (const row of this.results) {
        if (row && row.type === "video" && row.id) {
          next[row.id] = stamp;
        }
      }
      this.mediaCacheBust = next;
    },
    applyPrefs(prefs) {
      if (!prefs) {
        return;
      }
      this.suspendAuto = true;
      this.form.limit = prefs.page_size || 50;
      this.viewMode = prefs.default_view || "grid";
      const sort = prefs.sort_mode || "name_az";
      if (sort === "name_az") {
        this.form.sortField = "path";
        this.form.sortDir = "asc";
      } else if (sort === "name_za") {
        this.form.sortField = "path";
        this.form.sortDir = "desc";
      } else if (sort === "date_new_old") {
        this.form.sortField = "taken";
        this.form.sortDir = "asc";
      } else if (sort === "date_old_new") {
        this.form.sortField = "taken";
        this.form.sortDir = "desc";
      }
      this.suspendAuto = false;
    },
    applyLoadFromRoute() {
      const loadId = this.$route?.query?.load;
      if (!loadId) {
        return;
      }
      const autoRun = this.$route?.query?.run === "1";
      this.fetchSavedSearch(loadId, autoRun);
    },
    async fetchSavedSearch(id, autoRun) {
      try {
        const res = await fetch(`/api/saved-searches/${id}`);
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.showToast(apiErrorMessage(data.error, "search.saved_load_failed", "Failed to load saved search"));
          return;
        }
        const query = data.query_json || data.query;
        if (!query || typeof query !== "object") {
          this.showToast(this.$t("search.saved_invalid", "Saved search is invalid"));
          return;
        }
        this.applyQuery(query, data.name || "", { autoRun, id: data.id });
      } catch (err) {
        this.showToast(this.$t("search.saved_load_failed", "Failed to load saved search"));
      }
    },
    applyQuery(query, name, options = {}) {
      const { form, page, pageInput, folder } = this.builderFromQuery(query);
      this.suspendAuto = true;
      this.form = form;
      this.page = page;
      this.selectedFolder = folder || null;
      this.pageInput = pageInput;
      this.activeTagIndex = null;
      this.suggestions = [];
      this.suspendAuto = false;
      this.loadedSearchId = options.id || null;
      this.loadedSearchName = name || "";
      this.loadedQuery = JSON.parse(JSON.stringify(query));
      this.loadedSnapshot = this.snapshotFromQuery(this.loadedQuery);
      this.syncSearchSemanticFromWhere((query.where && typeof query.where === "object") ? query.where : {});
      this.persistFolderFilter();
      this.savedBanner = name ? `${this.$t("search.loaded_from_saved", "Loaded from saved search")}: ${name}` : "";
      if (options.autoRun) {
        this.$nextTick(() => {
          this.runSearch();
        });
      }
    },
    builderFromQuery(query) {
      const where = query.where && typeof query.where === "object" ? query.where : {};
      const builder = this.builderFromWhere(where);
      const folder = builder.folder || null;
      builder.form.sortField = query.sort && query.sort.field ? query.sort.field : "path";
      builder.form.sortDir = query.sort && query.sort.dir ? query.sort.dir : "asc";
      builder.form.limit = Number.isFinite(query.limit) ? query.limit : 50;
      const limit = builder.form.limit || 50;
      const offset = Number.isFinite(query.offset) ? query.offset : 0;
      builder.page = Math.floor(offset / limit) + 1;
      builder.pageInput = builder.page;
      builder.folder = folder;
      return builder;
    },
    builderFromWhere(where) {
      const items = Array.isArray(where.items) ? where.items : [];
      const includeGroup = items.find(
        (item) =>
          item &&
          item.group &&
          Array.isArray(item.items) &&
          item.items.every(
            (rule) => rule && rule.field === "tag" && rule.op === "is" && typeof rule.value === "string"
          )
      );
      const includeTags = includeGroup ? includeGroup.items.map((rule) => rule.value) : [];
      const excludeTags = items
        .filter(
          (item) =>
            item &&
            item.field === "tag" &&
            item.op === "is_not" &&
            typeof item.value === "string"
        )
        .map((item) => item.value);
      const pathItem = items.find((item) => item && item.field === "path");
      const idItem = items.find((item) => item && item.field === "id" && item.op === "is");
      const semanticTagItem = items.find((item) => item && item.field === "semantic_tag" && item.op === "is");
      const typeItem = items.find((item) => item && item.field === "type" && item.op === "is");
      const extItem = items.find((item) => item && item.field === "ext" && item.op === "is");
      const takenItem = items.find((item) => item && item.field === "taken");

      const folderRelPath = typeof where.folder_rel_path === "string" ? where.folder_rel_path.trim() : "";
      const folderId = Number.isInteger(where.folder_id) ? where.folder_id : null;

      const form = {
        tags: [],
        tagMode: includeGroup && includeGroup.group === "ANY" ? "ANY" : "ALL",
        path: pathItem && typeof pathItem.value === "string" ? pathItem.value : "",
        mediaIds: idItem
          ? (Array.isArray(idItem.value) ? idItem.value.join(", ") : String(idItem.value || ""))
          : "",
        dateOp: "after",
        date: "",
        start: "",
        end: "",
        type: typeItem && typeof typeItem.value === "string" ? typeItem.value : "",
        ext: extItem && typeof extItem.value === "string" ? extItem.value : "",
        onlyFavorites: !!where.only_favorites,
        hasNotes: !!where.has_notes,
        folderRecursive: !!where.folder_recursive,
        sortField: "path",
        sortDir: "asc",
        limit: 50
      };

      if (semanticTagItem && Number.isInteger(semanticTagItem.value)) {
        this.searchSemanticSelected = { id: semanticTagItem.value, name: `#${semanticTagItem.value}` };
        this.searchSemanticInput = `#${semanticTagItem.value}`;
      } else {
        this.searchSemanticSelected = null;
        this.searchSemanticInput = "";
      }
      this.searchSemanticIncludeDescendants = !!where.semantic_tag_descendants;

      includeTags.forEach((tag) => form.tags.push({ value: tag, mode: "include" }));
      excludeTags.forEach((tag) => form.tags.push({ value: tag, mode: "exclude" }));
      if (form.tags.length === 0) {
        form.tags = [{ value: "", mode: "include" }];
      }

      if (takenItem && typeof takenItem.op === "string") {
        if (takenItem.op === "between" && Array.isArray(takenItem.value)) {
          form.dateOp = "between";
          form.start = takenItem.value[0] || "";
          form.end = takenItem.value[1] || "";
        } else if (typeof takenItem.value === "string") {
          form.dateOp = takenItem.op;
          form.date = takenItem.value;
        }
      }

      const folder = folderRelPath
        ? {
            id: folderId,
            rel_path: folderRelPath,
            name: folderRelPath.split("/").filter(Boolean).pop() || folderRelPath
          }
        : null;

      return { form, page: 1, pageInput: 1, folder };
    },
    whereFromBuilder() {
      const items = [];
      const includeTags = this.form.tags
        .filter((t) => t.mode === "include")
        .map((t) => t.value.trim())
        .filter(Boolean);
      const excludeTags = this.form.tags
        .filter((t) => t.mode === "exclude")
        .map((t) => t.value.trim())
        .filter(Boolean);

      if (includeTags.length > 0) {
        items.push({
          group: this.form.tagMode,
          items: includeTags.map((tag) => ({ field: "tag", op: "is", value: tag }))
        });
      }
      if (excludeTags.length > 0) {
        excludeTags.forEach((tag) => {
          items.push({ field: "tag", op: "is_not", value: tag });
        });
      }
      if (this.form.path) {
        items.push({ field: "path", op: "contains", value: this.form.path });
      }
      if (this.searchSemanticSelected && this.searchSemanticSelected.id) {
        items.push({ field: "semantic_tag", op: "is", value: Number(this.searchSemanticSelected.id) });
      }
      const mediaIds = this.parseMediaIds(this.form.mediaIds);
      if (mediaIds.length === 1) {
        items.push({ field: "id", op: "is", value: mediaIds[0] });
      } else if (mediaIds.length > 1) {
        items.push({ field: "id", op: "is", value: mediaIds });
      }
      if (this.form.dateOp === "between") {
        if (this.form.start && this.form.end) {
          items.push({
            field: "taken",
            op: "between",
            value: [this.form.start, this.form.end]
          });
        }
      } else if (this.form.date) {
        items.push({ field: "taken", op: this.form.dateOp, value: this.form.date });
      }
      if (this.form.type) {
        items.push({ field: "type", op: "is", value: this.form.type });
      }
      if (this.form.ext) {
        items.push({ field: "ext", op: "is", value: this.form.ext });
      }

      const where = {
        group: "ALL",
        items,
        only_favorites: this.form.onlyFavorites,
        has_notes: !!this.form.hasNotes,
        folder_recursive: !!this.form.folderRecursive,
        semantic_tag_descendants: !!(this.searchSemanticSelected && this.searchSemanticIncludeDescendants)
      };
      if (this.selectedFolder && this.selectedFolder.rel_path) {
        where.folder_rel_path = this.selectedFolder.rel_path;
      }
      if (this.selectedFolder && this.selectedFolder.id && !this.form.folderRecursive) {
        // Tree-selected folder should filter direct files only.
        where.folder_id = this.selectedFolder.id;
      }
      return where;
    },
    async syncSearchSemanticFromWhere(where) {
      const items = Array.isArray(where.items) ? where.items : [];
      const semanticRule = items.find((item) => item && item.field === "semantic_tag" && item.op === "is" && Number.isInteger(item.value));
      if (!semanticRule) {
        this.searchSemanticSelected = null;
        this.searchSemanticInput = "";
        this.searchSemanticSuggestions = [];
        this.searchSemanticIncludeDescendants = false;
        return;
      }
      const id = Number(semanticRule.value || 0);
      if (!id) {
        return;
      }
      try {
        const res = await fetch(`/api/semantic-tags/${id}`);
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.item) {
          this.searchSemanticSelected = { id, name: `#${id}` };
          this.searchSemanticInput = `#${id}`;
          return;
        }
        this.searchSemanticSelected = data.item;
        this.searchSemanticInput = data.item.name || `#${id}`;
      } catch (_e) {
        this.searchSemanticSelected = { id, name: `#${id}` };
        this.searchSemanticInput = `#${id}`;
      }
    },
    snapshotFromQuery(query) {
      const snapshot = {
        where: query.where || {},
        sort: query.sort || {},
        limit: Number.isFinite(query.limit) ? query.limit : 50
      };
      return JSON.stringify(snapshot);
    },
    snapshotFromBuilder() {
      const query = {
        where: this.whereFromBuilder(),
        sort: { field: this.form.sortField, dir: this.form.sortDir },
        limit: this.form.limit || 50
      };
      return this.snapshotFromQuery(query);
    },
    resetToLoaded() {
      if (!this.loadedQuery) {
        return;
      }
      this.applyQuery(this.loadedQuery, this.loadedSearchName, { autoRun: false });
    },
    buildQuery() {
      const dateRe = /^\d{4}-\d{2}-\d{2}$/;
      const normalizeDate = (value) =>
        value
          .trim()
          .replace(/[\u2010-\u2015\u2212]/g, "-");

      const where = this.whereFromBuilder();
      if (this.searchSemanticInput.trim() && (!this.searchSemanticSelected || !this.searchSemanticSelected.id)) {
        this.error = this.$t("search.semantic_tag_select_valid", "Select a typed tag from the list");
        return null;
      }
      if (this.form.dateOp === "between") {
        if (this.form.start && this.form.end) {
          const start = normalizeDate(this.form.start);
          const end = normalizeDate(this.form.end);
          this.form.start = start;
          this.form.end = end;
          if (!dateRe.test(start) || !dateRe.test(end)) {
            this.error = this.$t("search.date_invalid", "Date must be YYYY-MM-DD");
            return null;
          }
          where.items = where.items.filter((item) => item.field !== "taken");
          where.items.push({ field: "taken", op: "between", value: [start, end] });
        }
      } else if (this.form.date) {
        const date = normalizeDate(this.form.date);
        this.form.date = date;
        if (!dateRe.test(date)) {
          this.error = this.$t("search.date_invalid", "Date must be YYYY-MM-DD");
          return null;
        }
        where.items = where.items.filter((item) => item.field !== "taken");
        where.items.push({ field: "taken", op: this.form.dateOp, value: date });
      }

      return {
        where,
        sort: { field: this.form.sortField, dir: this.form.sortDir },
        limit: this.form.limit || 50,
        offset: (this.page - 1) * (this.form.limit || 50)
      };
    },
    openSaveModal() {
      if (!this.currentUser) {
        this.showToast(this.$t("search.save_login_required", "Login required to save searches"));
        return;
      }
      const suggested = this.suggestedName();
      this.saveName = suggested;
      this.saveError = "";
      this.replaceOpen = false;
      this.saveOpen = true;
    },
    closeSaveModal() {
      this.saveOpen = false;
      this.saveError = "";
    },
    closeReplaceModal() {
      this.replaceOpen = false;
    },
    suggestedName() {
      const parts = [];
      const includeTags = this.form.tags
        .filter((t) => t.mode === "include")
        .map((t) => t.value.trim())
        .filter(Boolean);
      const excludeTags = this.form.tags
        .filter((t) => t.mode === "exclude")
        .map((t) => t.value.trim())
        .filter(Boolean);
      includeTags.forEach((tag) => parts.push(tag));
      excludeTags.forEach((tag) => parts.push(`NOT ${tag}`));

      if (this.form.dateOp === "between" && this.form.start && this.form.end) {
        parts.push(`from ${this.form.start} to ${this.form.end}`);
      } else if (this.form.dateOp === "after" && this.form.date) {
        parts.push(`after ${this.form.date}`);
      } else if (this.form.dateOp === "before" && this.form.date) {
        parts.push(`before ${this.form.date}`);
      }
      if (this.form.type) {
        parts.push(`type ${this.form.type}`);
      }
      if (this.form.ext) {
        parts.push(`ext ${this.form.ext}`);
      }
      if (this.form.path) {
        parts.push(`path ${this.form.path}`);
      }
      if (this.form.mediaIds) {
        parts.push(`ids ${this.form.mediaIds}`);
      }
      if (this.searchSemanticSelected && this.searchSemanticSelected.name) {
        parts.push(`typed ${this.searchSemanticSelected.name}`);
      }
      if (this.selectedFolder && this.selectedFolder.rel_path) {
        const recursiveSuffix = this.form.folderRecursive
          ? ` (${this.$t("search.folder_recursive", "Recursive").toLowerCase()})`
          : "";
        parts.push(`in ${this.selectedFolder.rel_path}${recursiveSuffix}`);
      }
      if (parts.length) {
        return parts.join(" · ");
      }
      const now = new Date();
      const stamp = now.toISOString().slice(0, 16).replace("T", " ");
      return `Search ${stamp}`;
    },
    async submitSave(replace) {
      const query = this.buildQuery();
      if (!query) {
        this.saveError = this.error || "Invalid query";
        return;
      }
      const name = this.saveName.trim();
      if (!name) {
        this.saveError = this.$t("saved_search.name_required", "Name is required");
        return;
      }
      this.loading = true;
      this.saveError = "";
      try {
        const res = await fetch("/api/saved-searches", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name, query, replace })
        });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json();
        if (res.status === 409 && data.error === "exists") {
          this.saveOpen = false;
          this.replaceOpen = true;
          return;
        }
        if (!res.ok) {
          this.saveError = apiErrorMessage(data.message || data.error, "search.save_failed", "Failed to save search");
          return;
        }
        this.saveOpen = false;
        this.replaceOpen = false;
        this.showToast(this.$t("search.saved_ok", "Saved"));
      } catch (err) {
        this.saveError = "Failed to save search";
      } finally {
        this.loading = false;
      }
    },

    selectFolder(folder) {
      if (!folder || !folder.rel_path) {
        return;
      }
      this.selectedFolder = {
        id: folder.id || null,
        rel_path: folder.rel_path,
        name: folder.name || folder.rel_path
      };
      this.persistFolderFilter();
      this.page = 1;
      this.runSearch();
    },
    clearFolderFilter() {
      this.selectedFolder = null;
      this.persistFolderFilter();
      this.page = 1;
      this.runSearch();
    },
    persistFolderFilter() {
      try {
        if (this.selectedFolder && this.selectedFolder.rel_path) {
          window.localStorage.setItem("wa_folder_filter", JSON.stringify(this.selectedFolder));
        } else {
          window.localStorage.removeItem("wa_folder_filter");
        }
      } catch (_err) {
        // ignore storage errors
      }
    },
    restoreFolderFilter() {
      try {
        const raw = window.localStorage.getItem("wa_folder_filter");
        if (!raw) {
          return;
        }
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed.rel_path !== "string" || parsed.rel_path.trim() === "") {
          return;
        }
        this.selectedFolder = {
          id: Number.isInteger(parsed.id) ? parsed.id : null,
          rel_path: parsed.rel_path.trim(),
          name: parsed.name || parsed.rel_path.split("/").filter(Boolean).pop() || parsed.rel_path
        };
      } catch (_err) {
        // ignore storage errors
      }
    },
    addTagRow() {
      this.form.tags.push({ value: "", mode: "include" });
    },
    clearTagRow(idx) {
      this.form.tags[idx].value = "";
      this.form.tags[idx].mode = "include";
      if (this.activeTagIndex === idx) {
        this.activeTagIndex = null;
        this.suggestions = [];
      }
    },
    setActiveTag(idx) {
      this.activeTagIndex = idx;
      this.onTagInput(idx);
    },
    onTagInput(idx) {
      if (this.suggestTimer) {
        clearTimeout(this.suggestTimer);
      }
      const value = this.form.tags[idx]?.value || "";
      if (value.trim().length < 2) {
        this.suggestions = [];
        return;
      }
      this.suggestTimer = setTimeout(() => {
        this.fetchSuggestions(value);
      }, 200);
    },
    async fetchSuggestions(query) {
      try {
        const qs = new URLSearchParams();
        qs.set("q", query);
        qs.set("limit", "12");
        const res = await fetch(`/api/tags?${qs.toString()}`);
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          return;
        }
        if (Array.isArray(data)) {
          this.suggestions = data;
        }
      } catch (err) {
        // ignore
      }
    },
    applySuggestion(tag) {
      if (this.activeTagIndex === null) {
        return;
      }
      this.form.tags[this.activeTagIndex].value = tag;
      this.suggestions = [];
    },
    onSearchSemanticInput() {
      this.searchSemanticSelected = null;
      if (this.searchSemanticSuggestTimer) {
        clearTimeout(this.searchSemanticSuggestTimer);
      }
      const q = this.normalizeBatchTag(this.searchSemanticInput);
      if (q.length < 2) {
        this.searchSemanticSuggestions = [];
        return;
      }
      this.searchSemanticSuggestTimer = setTimeout(async () => {
        try {
          const qs = new URLSearchParams();
          qs.set("q", q);
          qs.set("limit", "12");
          const res = await fetch(`/api/semantic-tags/lookup?${qs.toString()}`);
          if (this.handleAuthError(res)) {
            return;
          }
          const data = await res.json().catch(() => ({}));
          if (!res.ok) {
            this.searchSemanticSuggestions = [];
            return;
          }
          this.searchSemanticSuggestions = Array.isArray(data.items) ? data.items : [];
        } catch (_e) {
          this.searchSemanticSuggestions = [];
        }
      }, 200);
    },
    applySearchSemanticSuggestion(item) {
      this.searchSemanticSelected = item;
      this.searchSemanticInput = item.name || "";
      this.searchSemanticSuggestions = [];
    },
    clearSearchSemanticTag() {
      this.searchSemanticSelected = null;
      this.searchSemanticInput = "";
      this.searchSemanticSuggestions = [];
      this.searchSemanticIncludeDescendants = false;
    },
    parseMediaIds(raw) {
      if (typeof raw !== "string") {
        return [];
      }
      const out = [];
      for (const part of raw.split(/[\s,;]+/)) {
        if (!part) {
          continue;
        }
        const id = Number(part);
        if (Number.isInteger(id) && id > 0 && !out.includes(id)) {
          out.push(id);
        }
      }
      return out;
    },
    openTagHistoryModal() {
      this.historyOpen = true;
      this.historyPreviewHidden = false;
      if (!this.historyIds) {
        if (this.selectedIds.length) {
          this.historyIds = this.selectedIds.join(", ");
        } else if (this.form.mediaIds) {
          this.historyIds = this.form.mediaIds;
        }
      }
      this.fetchTagHistory(true);
    },
    closeTagHistoryModal() {
      this.historyOpen = false;
      this.historyLoading = false;
      this.historyError = "";
      this.historyItems = [];
      this.historyOffset = 0;
      this.historyTotal = 0;
      this.historyRestoreBusy = {};
      this.historyPreviewHidden = false;
    },
    async fetchTagHistory(resetOffset = false) {
      if (!this.historyOpen) {
        return;
      }
      if (resetOffset) {
        this.historyOffset = 0;
      }
      this.historyLoading = true;
      this.historyError = "";
      try {
        const params = new URLSearchParams();
        params.set("limit", String(Math.min(500, Math.max(1, Number(this.historyLimit || 100)))));
        params.set("offset", String(this.historyOffset || 0));
        if (this.historyIds.trim()) {
          params.set("ids", this.historyIds.trim());
        }
        const res = await fetch(`/api/admin/media/tag-edits?${params.toString()}`);
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.historyError = apiErrorMessage(data.error, "history.load_failed", "Failed to load tag changes");
          return;
        }
        this.historyItems = Array.isArray(data.items) ? data.items : [];
        this.historyTotal = Number(data.total || 0);
      } catch (_e) {
        this.historyError = this.$t("history.load_failed", "Failed to load tag changes");
      } finally {
        this.historyLoading = false;
      }
    },
    previewHistoryItem(item) {
      if (!item || !item.current_file_id) {
        return;
      }
      if (!item.current_type || !["image", "video"].includes(item.current_type)) {
        this.showToast(this.$t("search.preview_unsupported", "Preview not supported for this file type"));
        return;
      }
      this.historyPreviewHidden = true;
      this.detachedPreviewRows = [{
        id: item.current_file_id,
        type: item.current_type,
        path: item.rel_path,
        entity: "media",
        is_favorite: false
      }];
      if (item.current_type === "video") {
        this.videoViewerStartId = item.current_file_id;
        this.videoViewerOpen = true;
        this.viewerOpen = false;
      } else {
        this.viewerStartId = item.current_file_id;
        this.viewerOpen = true;
        this.videoViewerOpen = false;
      }
    },
    async restoreHistoryItem(item) {
      if (!item || !item.current_file_id) {
        return;
      }
      this.historyRestoreBusy = { ...this.historyRestoreBusy, [item.id]: true };
      try {
        const res = await fetch(`/api/admin/media/${item.current_file_id}/tags/restore`, {
          method: "POST"
        });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.showToast(apiErrorMessage(data.error, "search.restore_queue_failed", "Failed to queue restore"));
          return;
        }
        this.showToast(this.$t("search.restore_queued_for_file", { id: item.current_file_id }, "Restore queued for file {id}"));
        this.fetchTagHistory(false);
      } catch (_e) {
        this.showToast(this.$t("search.restore_queue_failed", "Failed to queue restore"));
      } finally {
        const next = { ...this.historyRestoreBusy };
        delete next[item.id];
        this.historyRestoreBusy = next;
      }
    },
    async openBatchTagModal() {
      if (!this.isAdmin) {
        return;
      }
      if (this.selectedIds.length === 0 && !(Number(this.total || 0) > 0)) {
        this.showToast(this.$t("search.batch_select_or_search_first", "Select media objects or run a search first"));
        return;
      }
      this.batchTagOpen = true;
      this.batchTagLoading = true;
      this.batchTagSubmitting = false;
      this.batchTagError = "";
      this.batchTagItems = [];
      this.batchTagAvailableRemoveTags = [];
      this.batchTagCommonTags = [];
      this.batchTagRemoveTags = [];
      this.batchTagAddInput = "";
      this.batchTagScope = this.selectedIds.length > 0 ? "selected" : "all_results";
      this.batchTagSuggestions = [];
      this.batchTagSummary = null;
      this.batchTagPreviewHidden = false;
      if (this.selectedIds.length === 0) {
        this.batchTagLoading = false;
        return;
      }
      try {
        const res = await fetch("/api/admin/media/tags/batch/preview", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ids: this.selectedIds })
        });
        if (this.handleAuthError(res)) {
          this.batchTagOpen = false;
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.batchTagError = apiErrorMessage(data.error, "search.batch_preview_failed", "Failed to load batch preview");
          return;
        }
        this.batchTagItems = Array.isArray(data.items) ? data.items : [];
        this.batchTagAvailableRemoveTags = Array.isArray(data.available_remove_tags) ? data.available_remove_tags : [];
        this.batchTagCommonTags = Array.isArray(data.common_tags) ? data.common_tags : [];
      } catch (_e) {
        this.batchTagError = this.$t("search.batch_preview_failed", "Failed to load batch preview");
      } finally {
        this.batchTagLoading = false;
      }
    },
    closeBatchTagModal() {
      this.batchTagOpen = false;
      this.batchTagLoading = false;
      this.batchTagSubmitting = false;
      this.batchTagError = "";
      this.batchTagItems = [];
      this.batchTagAvailableRemoveTags = [];
      this.batchTagCommonTags = [];
      this.batchTagRemoveTags = [];
      this.batchTagAddInput = "";
      this.batchTagScope = "selected";
      this.batchTagSuggestions = [];
      this.batchTagSummary = null;
      this.batchTagPreviewHidden = false;
      this.batchSemanticTagCreateOpen = false;
      if (this.batchTagSuggestTimer) {
        clearTimeout(this.batchTagSuggestTimer);
        this.batchTagSuggestTimer = null;
      }
    },
    toggleBatchRemoveTag(tag, checked) {
      if (checked) {
        if (!this.batchTagRemoveTags.includes(tag)) {
          this.batchTagRemoveTags = [...this.batchTagRemoveTags, tag].sort((a, b) => a.localeCompare(b));
        }
        return;
      }
      this.batchTagRemoveTags = this.batchTagRemoveTags.filter((item) => item !== tag);
    },
    normalizeBatchTag(raw) {
      if (typeof raw !== "string") {
        return "";
      }
      return raw.trim().replace(/\s+/g, " ");
    },
    semanticTypeLabel(type) {
      if (type === "person") return this.$t("semantic_tags.type_person", "Person");
      if (type === "event") return this.$t("semantic_tags.type_event", "Event");
      if (type === "category") return this.$t("semantic_tags.type_category", "Category");
      return this.$t("semantic_tags.type_generic", "Generic");
    },
    openBatchSemanticTagCreate() {
      if (!this.normalizeBatchTag(this.batchTagAddInput)) {
        return;
      }
      this.batchSemanticTagCreateOpen = true;
    },
    onBatchSemanticTagCreated(item) {
      this.batchSemanticTagCreateOpen = false;
      const name = item && item.name ? String(item.name) : this.normalizeBatchTag(this.batchTagAddInput);
      if (!name) {
        return;
      }
      this.batchTagAddInput = name;
      this.batchTagSuggestions = [];
      this.showToast(this.$t("semantic_tags.created_and_assigned", "Typed tag created and added"));
    },
    openSemanticAssignModal() {
      if (!this.isAdmin) {
        return;
      }
      if (this.batchTagOpenDisabled) {
        this.showToast(this.$t("semantic_tags.assign_select_or_search_first", "Select items or run a search first"));
        return;
      }
      this.semanticAssignOpen = true;
      this.semanticAssignLoading = false;
      this.semanticAssignSubmitting = false;
      this.semanticAssignError = "";
      this.semanticAssignScope = this.selectedIds.length > 0 ? "selected" : "all_results";
      this.semanticAssignInput = "";
      this.semanticAssignSelected = null;
      this.semanticAssignSuggestions = [];
      this.semanticAssignItems = [];
      this.semanticAssignCount = 0;
      this.semanticAssignCreateOpen = false;
      this.refreshSemanticAssignPreview();
    },
    closeSemanticAssignModal() {
      this.semanticAssignOpen = false;
      this.semanticAssignLoading = false;
      this.semanticAssignSubmitting = false;
      this.semanticAssignError = "";
      this.semanticAssignInput = "";
      this.semanticAssignSelected = null;
      this.semanticAssignSuggestions = [];
      this.semanticAssignItems = [];
      this.semanticAssignCount = 0;
      this.semanticAssignCreateOpen = false;
      if (this.semanticAssignSuggestTimer) {
        clearTimeout(this.semanticAssignSuggestTimer);
        this.semanticAssignSuggestTimer = null;
      }
    },
    openSemanticAssignCreate() {
      if (!this.normalizeBatchTag(this.semanticAssignInput)) {
        return;
      }
      this.semanticAssignCreateOpen = true;
    },
    onSemanticAssignCreated(item) {
      this.semanticAssignCreateOpen = false;
      if (!item || !item.id) {
        return;
      }
      this.semanticAssignSelected = item;
      this.semanticAssignInput = item.name || this.semanticAssignInput;
      this.semanticAssignSuggestions = [];
    },
    onSemanticAssignInput() {
      this.semanticAssignError = "";
      this.semanticAssignSelected = null;
      if (this.semanticAssignSuggestTimer) {
        clearTimeout(this.semanticAssignSuggestTimer);
      }
      const q = this.normalizeBatchTag(this.semanticAssignInput);
      if (q.length < 2) {
        this.semanticAssignSuggestions = [];
        return;
      }
      this.semanticAssignSuggestTimer = setTimeout(() => {
        this.fetchSemanticAssignSuggestions(q);
      }, 150);
    },
    async fetchSemanticAssignSuggestions(q) {
      try {
        const params = new URLSearchParams({ q, limit: "12" });
        const res = await fetch(`/api/admin/semantic-tags/lookup?${params.toString()}`);
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.semanticAssignSuggestions = [];
          return;
        }
        this.semanticAssignSuggestions = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.semanticAssignSuggestions = [];
      }
    },
    selectSemanticAssignSuggestion(item) {
      this.semanticAssignSelected = item;
      this.semanticAssignInput = item.name || "";
      this.semanticAssignSuggestions = [];
    },
    async refreshSemanticAssignPreview() {
      if (!this.semanticAssignOpen) {
        return;
      }
      this.semanticAssignLoading = true;
      this.semanticAssignError = "";
      try {
        const query = this.semanticAssignScope === "all_results" ? this.buildAllResultsQuery() : null;
        const res = await fetch("/api/admin/semantic-tags/assign-preview", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            apply_to: this.semanticAssignScope,
            ids: this.semanticAssignScope === "selected" ? this.selectedIds : [],
            search_query: query
          })
        });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.semanticAssignError = apiErrorMessage(data.error, "semantic_tags.assign_load_failed", "Failed to resolve assignment scope");
          return;
        }
        this.semanticAssignCount = Number(data.count || 0);
        this.semanticAssignItems = Array.isArray(data.items) ? data.items : [];
      } catch (_e) {
        this.semanticAssignError = this.$t("semantic_tags.assign_load_failed", "Failed to resolve assignment scope");
      } finally {
        this.semanticAssignLoading = false;
      }
    },
    async submitSemanticAssign() {
      if (!this.semanticAssignSelected || !this.semanticAssignSelected.id) {
        this.semanticAssignError = this.$t("semantic_tags.assign_select_tag_first", "Select or create a typed tag first");
        return;
      }
      if (this.semanticAssignScope === "all_results") {
        const confirmed = window.confirm(
          this.$t(
            "semantic_tags.assign_confirm_all_results",
            { name: this.semanticAssignSelected.name, count: this.semanticAssignCount },
            'Apply typed tag "{name}" to all {count} matching items?'
          )
        );
        if (!confirmed) {
          return;
        }
      }
      this.semanticAssignSubmitting = true;
      this.semanticAssignError = "";
      try {
        const query = this.semanticAssignScope === "all_results" ? this.buildAllResultsQuery() : null;
        const res = await fetch("/api/admin/semantic-tags/assign", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            apply_to: this.semanticAssignScope,
            ids: this.semanticAssignScope === "selected" ? this.selectedIds : [],
            search_query: query,
            semantic_tag_id: this.semanticAssignSelected.id
          })
        });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.semanticAssignError = apiErrorMessage(data.error, "semantic_tags.assign_submit_failed", "Failed to assign typed tag");
          return;
        }
        this.showToast(this.$t("semantic_tags.assign_done", { assigned: data.assigned_count || 0, skipped: data.skipped_count || 0 }, "Typed tag assigned to {assigned} items, skipped {skipped}."));
        this.closeSemanticAssignModal();
      } catch (_e) {
        this.semanticAssignError = this.$t("semantic_tags.assign_submit_failed", "Failed to assign typed tag");
      } finally {
        this.semanticAssignSubmitting = false;
      }
    },
    onBatchTagInput() {
      this.batchTagError = "";
      this.batchTagSummary = null;
      if (this.batchTagSuggestTimer) {
        clearTimeout(this.batchTagSuggestTimer);
      }
      const q = this.normalizeBatchTag(this.batchTagAddInput);
      if (q.length < 2) {
        this.batchTagSuggestions = [];
        return;
      }
      this.batchTagSuggestTimer = setTimeout(() => {
        this.fetchBatchTagSuggestions(q);
      }, 150);
    },
    async fetchBatchTagSuggestions(q) {
      try {
        const params = new URLSearchParams({ q, limit: "12" });
        const res = await fetch(`/api/tags?${params.toString()}`);
        if (!res.ok) {
          this.batchTagSuggestions = [];
          return;
        }
        const data = await res.json();
        if (!Array.isArray(data)) {
          this.batchTagSuggestions = [];
          return;
        }
        const currentAdd = this.normalizeBatchTag(this.batchTagAddInput);
        this.batchTagSuggestions = data
          .filter((item) => item && typeof item.tag === "string")
          .map((item) => ({ tag: item.tag, cnt: Number(item.cnt || 0) }))
          .filter((item) => item.tag !== currentAdd);
      } catch (_e) {
        this.batchTagSuggestions = [];
      }
    },
    applyBatchTagSuggestion(tag) {
      this.batchTagAddInput = tag;
      this.batchTagSuggestions = [];
    },
    previewBatchItem(item) {
      if (!item || item.status !== "ok") {
        return;
      }
      this.batchTagPreviewHidden = true;
      this.openViewer(item.id);
    },
    async submitBatchTagEdit() {
      if (this.batchTagSubmitting || !this.batchTagHasChangeRequest || this.batchTagConflict) {
        return;
      }
      const query = this.batchTagScope === "all_results" ? this.buildAllResultsQuery() : null;
      if (this.batchTagScope === "all_results" && !query) {
        this.batchTagError = this.$t("search.batch_submit_failed", "Failed to queue batch tag edit");
        return;
      }
      if (this.batchTagScope === "all_results") {
        const count = Number(this.total || 0);
        const confirmed = window.confirm(
          this.$t("search.batch_all_results_confirm", { count }, "Apply changes to all {count} matching items?")
        );
        if (!confirmed) {
          return;
        }
      }
      this.batchTagSubmitting = true;
      this.batchTagError = "";
      this.batchTagSummary = null;
      try {
        const res = await fetch("/api/admin/media/tags/batch", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            apply_to: this.batchTagScope,
            ids: this.batchTagScope === "selected" ? this.selectedIds : [],
            search_query: query,
            remove_tags: this.batchTagRemoveTags,
            add_tag: this.normalizeBatchTag(this.batchTagAddInput) || null
          })
        });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok && res.status !== 207) {
          this.batchTagError = apiErrorMessage(data.error, "search.batch_submit_failed", "Failed to queue batch tag edit");
          return;
        }
        this.batchTagSummary = data;
        const failures = Array.isArray(data.results)
          ? data.results.filter((item) => item && item.status === "error")
          : [];
        if (failures.length > 0) {
          this.batchTagError = failures.map((item) => `#${item.id}: ${item.error || "Failed"}`).join(" | ");
          return;
        }
        this.showToast(this.$t("search.batch_queued_summary", { queued: data.queued_count || 0, skipped: data.skipped_count || 0 }, "Batch queued: {queued}, skipped: {skipped}"));
        this.clearSelection();
        this.closeBatchTagModal();
      } catch (_e) {
        this.batchTagError = this.$t("search.batch_submit_failed", "Failed to queue batch tag edit");
      } finally {
        this.batchTagSubmitting = false;
      }
    },
    async runSearch(resetPage = false) {
      if (resetPage) {
        this.page = 1;
      }
      this.loading = true;
      this.error = "";
      this.debugInfo = "";
      try {
        const query = this.buildQuery();
        if (!query) {
          this.loading = false;
          return;
        }
        const res = await fetch(`/api/search${this.debug ? "?debug=1" : ""}`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(query)
        });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.error = apiErrorMessage(data.error, "search.failed", "Search failed");
          this.results = [];
          return;
        }
        if (Array.isArray(data.items)) {
          this.results = data.items;
          this.total = typeof data.total === "number" ? data.total : data.items.length;
          this.offset = typeof data.offset === "number" ? data.offset : 0;
          this.limit = typeof data.limit === "number" ? data.limit : this.form.limit || 50;
          this.pageInput = this.page;
          this.debugInfo = data.debug ? JSON.stringify(data.debug, null, 2) : "";
        } else {
          this.results = [];
          this.total = 0;
          this.pageInput = this.page;
        }
        this.selectedIds = [];
        this.viewerOpen = false;
        this.videoViewerOpen = false;
        this.assetViewerOpen = false;
        this.stopSlideshow();
        if (!this.canFavorite) {
          this.form.onlyFavorites = false;
        }
      } catch (err) {
        this.error = err.message || String(err);
        this.results = [];
      } finally {
        this.loading = false;
      }
    },
    buildAllResultsQuery() {
      const query = this.buildQuery();
      if (!query) {
        return null;
      }
      return {
        ...query,
        offset: 0,
        limit: Math.max(1, Number(this.total || this.form.limit || 50))
      };
    },
    nextPage() {
      if (this.page < this.totalPages) {
        this.page += 1;
        this.runSearch();
      }
    },
    prevPage() {
      if (this.page > 1) {
        this.page -= 1;
        this.runSearch();
      }
    },
    jumpToPage() {
      const target = Number(this.pageInput);
      if (!Number.isFinite(target)) {
        return;
      }
      const clamped = Math.min(Math.max(1, target), this.totalPages);
      if (clamped !== this.page) {
        this.page = clamped;
        this.runSearch();
      }
    },
    formatTs(ts) {
      const d = new Date(ts * 1000);
      return d.toISOString().slice(0, 10);
    },
    formatDateTime(value) {
      if (!value) {
        return "";
      }
      const normalized = String(value).replace(" ", "T");
      const date = new Date(normalized);
      if (Number.isNaN(date.getTime())) {
        return String(value);
      }
      return date.toLocaleString();
    },
    fileUrl(id) {
      const base = `${window.location.origin}/api/file?id=${id}`;
      const bust = this.mediaCacheBust[id];
      if (!bust) {
        return base;
      }
      return `${base}&v=${bust}`;
    },
    assetFileUrl(row) {
      if (!row || !row.asset_id) {
        return "";
      }
      return `${window.location.origin}/api/asset/file?id=${row.asset_id}`;
    },
    assetViewUrl(row) {
      if (!row || !row.asset_id) {
        return "";
      }
      return `${window.location.origin}/api/asset/view?id=${row.asset_id}`;
    },
    videoUrl(id) {
      const base = `${window.location.origin}/api/video?id=${id}`;
      const bust = this.mediaCacheBust[id];
      if (!bust) {
        return base;
      }
      return `${base}&v=${bust}`;
    },
    thumbUrl(rowOrId) {
      const row = typeof rowOrId === "object" && rowOrId !== null
        ? rowOrId
        : this.results.find((r) => r.id === rowOrId);
      if (row && row.entity === "asset") {
        if (row.type === "doc") {
          return `${window.location.origin}/api/asset/thumb?id=${row.asset_id}`;
        }
        return "";
      }
      const id = typeof rowOrId === "object" && rowOrId !== null ? rowOrId.id : rowOrId;
      const base = `${window.location.origin}/api/thumb?id=${id}`;
      const bust = this.mediaCacheBust[id];
      if (!bust) {
        return base;
      }
      return `${base}&v=${bust}`;
    },
    fileName(path) {
      if (!path) {
        return "";
      }
      const parts = path.split("/");
      return parts[parts.length - 1];
    },
    async copyLink(rowOrId) {
      const row = typeof rowOrId === "object" && rowOrId !== null
        ? rowOrId
        : this.results.find((r) => r.id === rowOrId);
      const text = row && row.entity === "asset" ? this.assetFileUrl(row) : this.fileUrl(rowOrId);
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(text);
        } else {
          const input = document.createElement("input");
          input.value = text;
          document.body.appendChild(input);
          input.select();
          document.execCommand("copy");
          document.body.removeChild(input);
        }
        this.showToast(this.$t("search.copy_ok", "Copied!"));
      } catch (err) {
        this.showToast(this.$t("search.copy_failed", "Copy failed"));
      }
    },
    async downloadSelected() {
      if (this.selectedIds.length === 0) {
        this.showToast(this.$t("search.select_files_first", "Please select files first (max 20)"));
        return;
      }
      if (this.selectedIds.length > 20) {
        this.showToast(this.$t("search.too_many_files", "More than 20 files selected, please unselect some"));
        return;
      }
      try {
        const res = await fetch("/api/download", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ids: this.selectedIds })
        });
        if (this.handleAuthError(res)) {
          return;
        }
        if (!res.ok) {
          const data = await res.json();
          this.showToast(apiErrorMessage(data.error, "search.download_failed", "Download failed"));
          return;
        }
        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        const disposition = res.headers.get("Content-Disposition") || "";
        const match = disposition.match(/filename=\"?([^\";]+)\"?/);
        link.download = match ? match[1] : "webalbum-selected.zip";
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (err) {
        this.showToast(this.$t("search.download_failed", "Download failed"));
      }
    },
    async requestTrash(row) {
      if (!this.isAdmin || !row || !row.id) {
        return;
      }
      const ok = window.confirm(`${this.$t("viewer.move_to_trash", "Move to Trash")}?
${row.path || row.rel_path || row.id}
This is reversible from Admin -> Trash.`);
      if (!ok) {
        return;
      }
      try {
        const res = await fetch("/api/admin/trash", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: row.id, type: row.type || "image" })
        });
        if (this.handleAuthError(res)) {
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          this.showToast(apiErrorMessage(data.error, "search.trash_failed", "Failed to move to trash"));
          return;
        }
        this.showToast(this.$t("search.trash_moved", "Moved to Trash"));
        await this.runSearch();
      } catch (_e) {
        this.showToast(this.$t("search.trash_failed", "Failed to move to trash"));
      }
    },
    showToast(message) {
      this.toast = message;
      setTimeout(() => {
        this.toast = "";
      }, 1500);
    },
    handleAuthError(res) {
      if (res.status === 401 || res.status === 403) {
        window.dispatchEvent(new CustomEvent("wa-auth-changed", { detail: null }));
        this.$router.push("/login");
        return true;
      }
      return false;
    },
    async toggleFavorite(fileId) {
      if (!this.canFavorite) {
        this.showToast(this.$t("search.favorites_login_required", "Login required to use favorites"));
        return;
      }
      const row = this.results.find((r) => r.id === fileId);
      if (!row) {
        return;
      }
      const prev = row.is_favorite;
      row.is_favorite = !prev;
      try {
        const res = await fetch("/api/favorites/toggle", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ file_id: fileId })
        });
        if (this.handleAuthError(res)) {
          row.is_favorite = prev;
          return;
        }
        const data = await res.json();
        if (!res.ok) {
          row.is_favorite = prev;
          this.showToast(apiErrorMessage(data.error, "search.favorite_toggle_failed", "Failed to toggle favorite"));
          return;
        }
        row.is_favorite = !!data.is_favorite;
      } catch (err) {
        row.is_favorite = prev;
        this.showToast(this.$t("search.favorite_toggle_failed", "Failed to toggle favorite"));
      }
    },
    clearSelection() {
      this.selectedIds = [];
    },
    clearCriteria() {
      const prefs = window.__wa_prefs || null;
      const pageSize = prefs && prefs.page_size ? prefs.page_size : 50;
      this.suspendAuto = true;
      this.form = {
        tags: [{ value: "", mode: "include" }],
        tagMode: "ALL",
        path: "",
        mediaIds: "",
        dateOp: "after",
        date: "",
        start: "",
        end: "",
        type: "",
        ext: "",
        onlyFavorites: false,
        hasNotes: false,
        folderRecursive: false,
        sortField: "path",
        sortDir: "asc",
        limit: pageSize
      };
      this.page = 1;
      this.pageInput = 1;
      this.activeTagIndex = null;
      this.suggestions = [];
      this.clearSearchSemanticTag();
      this.error = "";
      this.savedBanner = "";
      this.loadedSearchId = null;
      this.loadedSearchName = "";
      this.loadedQuery = null;
      this.loadedSnapshot = "";
      this.selectedFolder = null;
      this.persistFolderFilter();
      this.suspendAuto = false;
      this.runSearch();
    },
    openViewer(id) {
      const row = this.results.find((r) => r.id === id);
      if (!row) {
        return;
      }
      if (row.entity === "asset") {
        this.viewerOpen = false;
        this.videoViewerOpen = false;
        this.assetViewerOpen = false;
        this.assetViewerRow = row;
        this.assetViewerError = "";
        this.assetViewerOpen = true;
        this.syncAssetSlideshow();
        return;
      }
      if (row.type === "video") {
        this.assetViewerOpen = false;
        this.viewerOpen = false;
        this.videoViewerStartId = id;
        this.videoViewerOpen = true;
        this.clearSlideshowTimer();
        return;
      }
      if (row.type !== "image") {
        this.showToast("Preview not supported for this file type");
        return;
      }
      this.assetViewerOpen = false;
      this.videoViewerOpen = false;
      this.viewerStartId = id;
      this.viewerOpen = true;
      this.clearSlideshowTimer();
    },
    closeViewer() {
      this.viewerOpen = false;
      this.clearSlideshowTimer();
      this.detachedPreviewRows = [];
      if (this.batchTagOpen && this.batchTagPreviewHidden) {
        this.batchTagPreviewHidden = false;
      }
      if (this.historyOpen && this.historyPreviewHidden) {
        this.historyPreviewHidden = false;
      }
      if (this.slideshowActive) {
        this.stopSlideshow();
      }
    },
    openAssetFromImageViewer(row) {
      if (!row) {
        return;
      }
      this.viewerOpen = false;
      this.videoViewerOpen = false;
      this.assetViewerError = "";
      this.assetViewerRow = row;
      this.assetViewerOpen = true;
      this.syncAssetSlideshow();
    },
    openVideoFromImageViewer(id) {
      const targetId = Number(id || 0);
      if (!targetId) {
        return;
      }
      this.viewerOpen = false;
      this.assetViewerOpen = false;
      this.videoViewerStartId = targetId;
      this.videoViewerOpen = true;
      this.clearSlideshowTimer();
    },
    openAssetFromVideoViewer(row) {
      if (!row) {
        return;
      }
      this.videoViewerOpen = false;
      this.viewerOpen = false;
      this.assetViewerError = "";
      this.assetViewerRow = row;
      this.assetViewerOpen = true;
      this.syncAssetSlideshow();
    },
    openImageFromVideoViewer(id) {
      const targetId = Number(id || 0);
      if (!targetId) {
        return;
      }
      this.videoViewerOpen = false;
      this.assetViewerOpen = false;
      this.viewerStartId = targetId;
      this.viewerOpen = true;
      this.clearSlideshowTimer();
    },
    navigateFromAssetViewerIndex(targetIndex) {
      const row = this.results[targetIndex] || null;
      if (!row) {
        return;
      }
      if (row.entity === "asset") {
        this.assetViewerRow = row;
        this.assetViewerError = "";
        this.syncAssetSlideshow();
        return;
      }
      this.assetViewerOpen = false;
      this.clearSlideshowTimer();
      if (row.type === "image") {
        this.viewerStartId = row.id;
        this.viewerOpen = true;
        return;
      }
      if (row.type === "video") {
        this.videoViewerStartId = row.id;
        this.videoViewerOpen = true;
        return;
      }
      this.showToast("Preview not supported for this file type");
    },
    assetPrev() {
      if (this.assetViewerIndex <= 0) {
        return;
      }
      this.navigateFromAssetViewerIndex(this.assetViewerIndex - 1);
    },
    assetNext() {
      if (this.assetViewerIndex < 0 || this.assetViewerIndex >= this.results.length - 1) {
        return;
      }
      this.navigateFromAssetViewerIndex(this.assetViewerIndex + 1);
    },
    closeVideoViewer() {
      this.videoViewerOpen = false;
      this.clearSlideshowTimer();
      this.detachedPreviewRows = [];
      if (this.batchTagOpen && this.batchTagPreviewHidden) {
        this.batchTagPreviewHidden = false;
      }
      if (this.historyOpen && this.historyPreviewHidden) {
        this.historyPreviewHidden = false;
      }
      if (this.slideshowActive) {
        this.stopSlideshow();
      }
    },
    closeAssetViewer() {
      this.clearSlideshowTimer();
      this.stopAssetAudioPlayback();
      this.assetViewerOpen = false;
      this.assetViewerRow = null;
      this.assetViewerError = "";
      this.detachedPreviewRows = [];
      if (this.batchTagOpen && this.batchTagPreviewHidden) {
        this.batchTagPreviewHidden = false;
      }
      if (this.historyOpen && this.historyPreviewHidden) {
        this.historyPreviewHidden = false;
      }
      if (this.slideshowActive) {
        this.stopSlideshow();
      }
    },
    openAssetOriginal() {
      if (!this.assetViewerRow) {
        return;
      }
      const url = this.assetFileUrl(this.assetViewerRow);
      if (!url) {
        return;
      }
      window.open(url, "_blank", "noopener");
    },
    openObjectPage(row) {
      if (!row) {
        return;
      }
      this.stopSlideshow();
      this.viewerOpen = false;
      this.videoViewerOpen = false;
      this.assetViewerOpen = false;
      const query = {};
      if (row.entity === "asset" && row.asset_id) {
        query.asset_id = String(row.asset_id);
      } else if (row.id) {
        query.file_id = String(row.id);
      } else {
        this.showToast(this.$t("search.object_ref_missing", "Object reference missing"));
        return;
      }
      this.$router.push({ path: "/object", query });
    },
    async onItemTrashed() {
      this.stopSlideshow();
      this.viewerOpen = false;
      this.videoViewerOpen = false;
      this.assetViewerOpen = false;
      this.showToast(this.$t("search.trash_moved", "Moved to Trash"));
      this.selectedIds = [];
      await this.runSearch();
    },
    onMediaRotated(payload) {
      const id = Number(payload && payload.id ? payload.id : 0);
      if (!id) {
        return;
      }
      const stamp = Number(payload && payload.at ? payload.at : Date.now());
      this.mediaCacheBust = {
        ...this.mediaCacheBust,
        [id]: stamp
      };
    },
    sortDirLabel(dir) {
      if (this.form.sortField === "taken") {
        return dir === "asc" ? "New-Old" : "Old-New";
      }
      return dir === "asc" ? "A-Z" : "Z-A";
    }
  },
  watch: {
    "form.dateOp"(next, prev) {
      if (next === "between" && prev === "after") {
        if (this.form.date && !this.form.start) {
          this.form.start = this.form.date;
        }
      }
    },
    "$route.query.load"() {
      this.applyLoadFromRoute();
    },
    "form.type"() {
      if (this.suspendAuto) {
        return;
      }
      this.page = 1;
      this.runSearch();
    },
    "form.ext"() {
      if (this.suspendAuto) {
        return;
      }
      this.page = 1;
      this.runSearch();
    },
    "form.sortField"() {
      if (this.suspendAuto) {
        return;
      }
      this.page = 1;
      this.runSearch();
    },
    "form.sortDir"() {
      if (this.suspendAuto) {
        return;
      }
      this.page = 1;
      this.runSearch();
    },
    "form.limit"() {
      if (this.suspendAuto) {
        return;
      }
      this.page = 1;
      this.runSearch();
    },
    "form.onlyFavorites"() {
      if (this.suspendAuto) {
        return;
      }
      this.page = 1;
      this.runSearch();
    },
    "form.hasNotes"() {
      if (this.suspendAuto) {
        return;
      }
      this.page = 1;
      this.runSearch();
    },
    slideshowSeconds(next) {
      const parsed = Number(next);
      const clamped = Math.min(3600, Math.max(1, Number.isFinite(parsed) ? parsed : 5));
      if (clamped !== next) {
        this.slideshowSeconds = clamped;
        return;
      }
      if (this.slideshowActive) {
        this.syncAssetSlideshow();
      }
    },
    viewMode() {}
  },
  computed: {
    assetViewerIndex() {
      if (!this.assetViewerRow) {
        return -1;
      }
      return this.results.findIndex((r) => r.id === this.assetViewerRow.id);
    },
    viewerResults() {
      return this.detachedPreviewRows.length ? this.detachedPreviewRows : this.results;
    },
    resultsSummary() {
      if (this.total === null) {
        return this.$t("search.results_empty", "Results: —");
      }
      const total = Number(this.total || 0);
      if (total <= 0) {
        return `${this.$t("results.title", "Results")}: 0 of 0 (0 items)`;
      }
      const perPage = Math.max(1, Number(this.form.limit || 50));
      const currentPage = Math.max(1, Number(this.page || 1));
      const start = Math.min(((currentPage - 1) * perPage) + 1, total);
      const end = Math.min(currentPage * perPage, total);
      return `${this.$t("results.title", "Results")}: ${start}-${end} of ${total} (${total} items)`;
    },
    totalPages() {
      if (this.total === null || this.total === 0) {
        return 1;
      }
      const perPage = this.form.limit || 50;
      return Math.max(1, Math.ceil(this.total / perPage));
    },
    canFavorite() {
      return !!this.currentUser;
    },
    isAdmin() {
      return !!(this.currentUser && this.currentUser.is_admin);
    },
    isModified() {
      if (!this.loadedSnapshot) {
        return false;
      }
      return this.snapshotFromBuilder() !== this.loadedSnapshot;
    },
    batchTagEligibleCount() {
      return this.batchTagItems.filter((item) => item && item.status === "ok").length;
    },
    batchTagHasChangeRequest() {
      return this.batchTagRemoveTags.length > 0 || this.normalizeBatchTag(this.batchTagAddInput) !== "";
    },
    batchTagConflict() {
      const addTag = this.normalizeBatchTag(this.batchTagAddInput);
      return addTag !== "" && this.batchTagRemoveTags.includes(addTag);
    },
    batchTagOpenDisabled() {
      return this.selectedIds.length === 0 && !(Number(this.total || 0) > 0);
    }
  }
};
</script>


<style scoped>
.search-layout {
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: 14px;
  align-items: start;
}
.folders-sidebar {
  position: sticky;
  top: 12px;
}
.search-main {
  min-width: 0;
}
.folder-pill {
  max-width: 420px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
@media (max-width: 980px) {
  .search-layout {
    grid-template-columns: 1fr;
  }
  .folders-sidebar {
    position: static;
  }
}
</style>

<style scoped>
.asset-modal {
  width: 80vw;
  max-width: 80vw;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

.asset-body {
  margin-top: 8px;
  flex: 1;
  min-height: 0;
}

.asset-body audio {
  width: 100%;
}

.slideshow-control {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.slideshow-control input {
  width: 72px;
}

.doc-body iframe {
  width: 100%;
  height: 100%;
  min-height: 60vh;
  border: 1px solid #d6c9b5;
  border-radius: 8px;
  background: #fff;
}

.batch-tag-modal {
  width: min(1100px, 96vw);
  max-height: 88vh;
  display: flex;
  flex-direction: column;
}

.batch-tag-layout {
  display: grid;
  grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
  gap: 16px;
  min-height: 0;
}

.batch-tag-controls,
.batch-tag-items {
  min-height: 0;
}

.batch-tag-items {
  overflow: auto;
  border: 1px solid #d6c9b5;
  border-radius: 12px;
  padding: 10px;
  background: #fffaf2;
}

.batch-tag-remove {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 12px;
}

.batch-tag-check {
  display: flex;
  align-items: center;
  gap: 8px;
}

.batch-tag-common {
  margin-top: 12px;
}

.batch-tag-label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
}

.batch-tag-item {
  display: grid;
  grid-template-columns: 96px minmax(0, 1fr) auto;
  gap: 12px;
  align-items: start;
  padding: 10px 0;
  border-bottom: 1px solid #eadfcf;
}

.batch-tag-item:last-child {
  border-bottom: 0;
}

.batch-tag-thumb {
  width: 96px;
}

.batch-tag-thumb img {
  width: 96px;
  height: 96px;
  object-fit: cover;
  border-radius: 10px;
}

.batch-tag-meta {
  min-width: 0;
}

.batch-tag-path {
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.batch-tag-status {
  font-size: 12px;
  margin: 4px 0 8px;
  color: #6f6556;
}

.batch-tag-status.status-error {
  color: #b42318;
}

.batch-tag-status.status-unsupported {
  color: #9a6700;
}

.batch-tag-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tag-chip-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.tag-pill {
  display: inline-flex;
  align-items: center;
  padding: 3px 8px;
  border-radius: 999px;
  background: #efe2c9;
  color: #2b2b2b;
  font-size: 12px;
}

.batch-tag-suggestions {
  margin-top: 8px;
}

.tag-change-row {
  margin-top: 8px;
}

@media (max-width: 980px) {
  .batch-tag-layout {
    grid-template-columns: 1fr;
  }

  .batch-tag-item {
    grid-template-columns: 72px minmax(0, 1fr);
  }

  .batch-tag-thumb,
  .batch-tag-thumb img {
    width: 72px;
    height: 72px;
  }

  .batch-tag-actions {
    grid-column: 1 / -1;
    flex-direction: row;
  }
}
</style>
