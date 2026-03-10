        <?php
          error_reporting(E_ERROR | E_PARSE);
          set_time_limit(300);

          define('BASE_PATH', dirname(__DIR__)); // /api -> project root
          require_once BASE_PATH . '/core/___modules.php';

          // Inputs
          $url  = trim($_POST['url']  ?? $_GET['url']  ?? '');
          $text = trim($_POST['text'] ?? '');

          $sourceType = ($text !== '') ? 'text' : 'url';

          // Run correct analysis
          if ($text !== '') {

              // Text analysis mode
              $arr = azeo_text_results($text);

          } elseif ($url !== '') {

              // URL analysis mode
              $arr = azeo_site_results($url);

          } else {

              http_response_code(400);
              echo json_encode([
                  'success' => false,
                  'error' => 'No url or text provided'
              ]);
              exit;

          }


          if (($arr["error"] == "No features in text.")  || empty($arr['entities'])) {
            echo render_empty_analytics($url, 'No features in text.', $sourceType);
            exit;
          }


          // Example: If NLP or screenshot fails
          if (empty($arr)) {
              header("Location: newsroom.php?url=" . urlencode($url) . "&error=1");
              exit;
          }

          // JSON mode, right now just returns entities from the NLP API
          $format = strtolower($_GET['format'] ?? $_POST['format'] ?? '') === 'json';

          if ($format) {
              header('Content-Type: application/json; charset=utf-8');

              // Make sure entities are a flat array of strings
              $entities = array_values(array_unique(array_filter(array_map('trim', $arr['entities'] ?? []))));
              echo json_encode([
                  'summary'  => $arr['summary'] ?? null,
                  'entities' => $entities
              ], JSON_UNESCAPED_SLASHES);
              exit;
          }

        ?>
        

        <?php require_once BASE_PATH . "/views/newsroom/___nlp_body.php"; ?>


        <?php
          function render_empty_analytics(string $url = '', string $reason = 'No features in text.', string $sourceType = 'url') {
            $host = parse_url($url, PHP_URL_HOST) ?: 'this page';
            $isText = ($sourceType === 'text');

            $message = $isText
              ? 'We couldn’t find enough readable text in your submission to compute keywords, entities, narrative frames, or sentiment.'
              : 'We couldn’t find enough readable text on <span class="fw-semibold">' . htmlspecialchars($host) . '</span> to compute keywords, entities, narrative frames, or sentiment.';

            $actions = $isText
              ? ''
              : '<div class="d-flex gap-2">
                  <a class="btn btn-sm btn-outline-secondary mr-2" href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener">Open article</a>
                  <button class="btn btn-sm btn-primary" onclick="reanalyzeAnalytics(\'' . htmlspecialchars($url, ENT_QUOTES) . '\')">Retry</button>
                </div>';

            $whyList = $isText
              ? '
                <ul class="mb-0 ps-3">
                  <li>Very short text sample</li>
                  <li>Mostly headings or fragments</li>
                  <li>Not enough context for NLP extraction</li>
                </ul>'
              : '
                <ul class="mb-0 ps-3">
                  <li>Video/live page or gallery</li>
                  <li>Very short post or headline-only</li>
                  <li>Paywall or script-rendered content</li>
                </ul>';

            return <<<HTML
          <div class="card shadow-sm border-0 empty-analytics">
            <div class="card-body d-flex align-items-start gap-3">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" fill="#eef2ff"></circle>
                <path d="M12 7v6" stroke="#6366f1" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="16" r="1.5" fill="#6366f1"/>
              </svg>
              <div>
                <h6 class="mb-1">Nothing to analyze</h6>
                <p class="mb-2 text-muted small">
                  {$message}
                </p>
                {$actions}
                <details class="mt-2 small text-muted">
                  <summary class="pointer">Why?</summary>
                  {$whyList}
                </details>
              </div>
            </div>
          </div>
          HTML;
          }
          ?>