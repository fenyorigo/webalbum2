import { t } from "./i18n";

const API_ERROR_KEYS = {
  "Not authenticated": "api.not_authenticated",
  Forbidden: "api.forbidden",
  "Not Found": "api.not_found",
  "Invalid JSON": "api.invalid_json",
  "File not found": "api.file_not_found",
  "Asset file not found": "api.asset_file_not_found",
  Trashed: "api.trashed",
  "Invalid name": "api.invalid_name",
  "Invalid query": "api.invalid_query",
  "No updates": "api.no_updates",
  "id is required": "api.id_required",
  "Invalid file_id": "api.invalid_file_id",
  "file_id must be an integer": "api.invalid_file_id",
  "Invalid user_id": "api.invalid_user_id",
  "user_id must be an integer": "api.invalid_user_id",
  "User not found": "api.user_not_found",
  "MariaDB unavailable": "api.mariadb_unavailable",
  "Invalid credentials": "api.invalid_credentials",
  "Too many login attempts. Try again later.": "api.too_many_login_attempts",
  "Missing fields": "api.missing_fields",
  "Passwords do not match": "api.passwords_do_not_match",
  "Current password is incorrect": "api.current_password_incorrect",
  "Invalid username": "api.invalid_username",
  "Username already exists": "api.username_exists",
  "Password is required": "api.password_required",
  "Password must be at least 8 characters": "api.password_too_short",
  "Cannot delete your own user": "api.cannot_delete_own_user",
  "Saved search already exists": "api.saved_search_exists",
  "Invalid language code": "api.invalid_language_code",
  "Language names are required": "api.language_names_required",
  "Language already exists": "api.language_exists",
  "Invalid string key": "api.invalid_string_key",
  "Default English text is required": "api.default_english_required",
  "String key already exists": "api.string_key_exists",
  "String key is required": "api.string_key_required",
  "Translated text is required": "api.translated_text_required",
  "Language not found": "api.language_not_found",
  "Cannot favorite trashed media": "api.favorite_trashed_forbidden",
  "Only images are supported": "api.only_images_supported",
  "Only videos are supported": "api.only_videos_supported",
  "Please select files first (max 20)": "api.download_select_files_first",
  "More than 20 files selected, please unselect some": "api.download_too_many_files",
  "Some selected media files were not found": "api.download_media_missing",
  "Some selected assets were not found": "api.download_assets_missing",
  "Trashed media cannot be downloaded": "api.download_trashed_forbidden",
  "Only image/video media files are supported": "api.download_only_media_supported",
  "Only audio/document assets are supported": "api.download_only_assets_supported",
  "File outside configured photos root": "api.file_outside_root",
  "Asset outside configured photos root": "api.asset_outside_root",
  "No files selected": "api.no_files_selected",
  "Setup already completed": "api.setup_completed",
  "query must be an object": "api.query_must_be_object",
  "query.where is required": "api.query_where_required"
};

export function apiErrorMessage(serverMessage, fallbackKey = "", fallbackText = "") {
  const message = typeof serverMessage === "string" ? serverMessage.trim() : "";
  if (message && API_ERROR_KEYS[message]) {
    return t(API_ERROR_KEYS[message], message);
  }
  if (message) {
    return message;
  }
  if (fallbackKey) {
    return t(fallbackKey, fallbackText || fallbackKey);
  }
  return fallbackText || "";
}
