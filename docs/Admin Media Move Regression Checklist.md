# Admin Media Move Regression Checklist

## 1. Basic Move Behavior

- [ ] As admin, open an indexed image or video from preview and verify the `Move` action is visible.
- [ ] Select a valid destination folder from the folder tree and confirm the move.
- [ ] Verify the file is moved from folder A to folder B on disk.
- [ ] Verify the moved media opens correctly from its new location.
- [ ] Move the same media back to its original folder.
- [ ] Verify the roundtrip leaves only one current result, not duplicates.

## 2. MariaDB-Linked State Preservation

- [ ] If the media is favorited, verify it remains in Favorites after move.
- [ ] If the media has object-linked notes or collaboration state, verify they are still visible after move.
- [ ] If the media has manual semantic media links, verify they still resolve after move.
- [ ] If the media has tag-edit history, verify history remains continuous after move and is not split by old and new path.

## 3. Guard and Blocking Behavior

- [ ] Verify move is blocked when there is an open tag edit for the media.
- [ ] Verify move is blocked when a queued or running `media_tag_edit` job exists for the media.
- [ ] Verify move is blocked when a queued or running transform job exists for the resolved object.
- [ ] Verify the blocking message is clear and localized.

## 4. Failure Handling

- [ ] Verify an invalid destination folder is rejected clearly.
- [ ] Verify moving into a folder that already contains the same filename is rejected clearly.
- [ ] Simulate indexer failure and verify the user gets a clear failure message.
- [ ] After indexer failure, verify the file is restored or the failure clearly reports rollback failure.
- [ ] Simulate MariaDB remap failure and verify the user gets a clear failure message.
- [ ] After MariaDB remap failure, verify the file is restored or the failure clearly reports rollback failure.

## 5. UI and Feedback

- [ ] Verify non-admin users do not see the `Move` action.
- [ ] Verify non-admin API access to the move endpoint is rejected.
- [ ] Verify destination selection via folder tree works and shows the expected hierarchy.
- [ ] Verify success toast or message is localized and shown after a successful move.
- [ ] Verify failure toast or message is localized and shown on blocked or failed moves.

## 6. Post-Move Consistency

- [ ] Verify search results show the media at the new path.
- [ ] Verify the old path no longer appears in search results.
- [ ] Verify preview or viewer opens the moved media from the new path without broken state.
- [ ] Verify thumbnails regenerate or remain usable after move.
- [ ] Verify an audit or log entry exists for success and for failure or block cases.

## Minimum Smoke Test

- [ ] Admin moves one indexed image from folder A to folder B successfully.
- [ ] Search shows only the new path, not the old one.
- [ ] Preview opens correctly after move.
- [ ] Favorite survives the move.
- [ ] Move back to original folder succeeds.
- [ ] Open tag edit blocks move.
- [ ] Name collision is rejected clearly.
- [ ] Success and failure messages are localized and understandable.
