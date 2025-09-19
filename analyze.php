        <?php
          error_reporting(E_ERROR | E_PARSE);

          set_time_limit(300);
  
          require_once('___modules.php');

          $url = trim($_POST['url'] ?? $_GET['url'] ?? '');

          // get analytics
          $arr = azeo_site_results($url);


          if (($arr["error"] == "No features in text.")  || empty($arr['entities'])) {
            echo render_empty_analytics($url);
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
        

        <?php require_once("___nlp_body.php"); ?>