<?php

/**
 * @file
 * Hooks provided by the Mail Safety module.
 */

/**
 * Respond to a mail being inserted to the dashboard.
 *
 * @param array $message
 *   The message array.
 *
 * @return array
 *   Return the message array after any changes.
 */
function hook_mail_safety_pre_insert(array $message) {
  // Check to see if there are attachments.
  if (!empty($message['params']['attachments'])) {
    // Loop through the attachments and save the files.
    foreach ($message['params']['attachments'] as $key => $attachment) {
      $file = file_save_data($attachment['content'], 'public://' . time() . '-' . $attachment['filename']);
      $message['attachments'][$key] = $file;
    }
    // Remove the attachments from the e-mail.
    unset($message['params']['attachments']);
  }
  return $message;
}

/**
 * Respond to a mail being loaded.
 *
 * @param array $message
 *   The message array.
 *
 * @return array
 *   Return the message array after any changes.
 */
function hook_mail_safety_load(array $message) {
  if (!empty($message['attachments'])) {
    $message['has_attachments'] = TRUE;
  }
  return $message;
}

/**
 * Respond to a mail before it is being send.
 *
 * @param array $message
 *   The message array.
 *
 * @return array
 *   Return the message array after any changes.
 */
function hook_mail_safety_pre_send(array $message) {
  // Loop through the attachments in a message.
  foreach ($message['attachments'] as $key => $attachment) {
    // Return the attachment to the e-mail to send it again.
    $message['params']['attachments'][$key] = [
      'content' => file_get_contents($attachment->uri),
      'mime' => $attachment->filemime,
      'filename' => $attachment->filename,
    ];
  }
  return $message;
}

/**
 * Alter the table structure of the mail safety dashboard.
 *
 * @param array $table_structure
 *   The table structure that will be rendered as table.
 */
function hook_mail_safety_table_structure_alter(array $table_structure) {
  // Add a new column.
  $table_structure['header'][] = [
    'data' => t('Files'),
  ];

  // Loop through the mails to add the attachments in the table.
  foreach ($table_structure['rows'] as $mail_id => $row) {
    $mail = mail_safety_load($mail_id);

    if (!empty($mail['mail']['attachments'])) {
      $attachments = [];
      foreach ($mail['mail']['attachments'] as $attachment) {
        $attachments[] = [
          '#theme' => 'file_link',
          '#file' => $attachment,
        ];
      }
    }
    $table_structure['rows'][$mail_id]['data'][] = $attachments;
  }

  return $table_structure;
}
