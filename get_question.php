<?php
require_once 'admin_auth.php';
$conn = new mysqli('localhost', 'root', '', 'helpdesk_db');

$id = (int)$_GET['id'];
$question = $conn->query("SELECT * FROM faq_questions WHERE id = $id")->fetch_assoc();

if (!$question) die("Question not found");

echo '<input type="hidden" name="id" value="' . $question['id'] . '">
      <div class="mb-3">
          <label class="form-label">User</label>
          <input type="text" class="form-control" value="' . htmlspecialchars($question['name']) . ' (' . htmlspecialchars($question['email']) . ')" readonly>
      </div>
      <div class="mb-3">
          <label class="form-label">Question</label>
          <textarea class="form-control" rows="4" readonly>' . htmlspecialchars($question['question']) . '</textarea>
      </div>
      <div class="mb-3">
          <label class="form-label">Answer</label>
          <textarea class="form-control" name="answer" rows="6" required>' . htmlspecialchars($question['answer'] ?? '') . '</textarea>
      </div>
      <div class="row">
          <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                  <option value="pending" ' . ($question['status'] == 'pending' ? 'selected' : '') . '>Pending</option>
                  <option value="answered" ' . ($question['status'] == 'answered' ? 'selected' : '') . '>Answered</option>
                  <option value="rejected" ' . ($question['status'] == 'rejected' ? 'selected' : '') . '>Rejected</option>
              </select>
          </div>
      </div>
      <div class="mb-3">
          <label class="form-label">Admin Notes</label>
          <textarea class="form-control" name="admin_notes" rows="3">' . htmlspecialchars($question['admin_notes'] ?? '') . '</textarea>
          <div class="form-text">Internal notes only (not visible to user)</div>
      </div>';
