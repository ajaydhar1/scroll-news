<button class="btn btn-small btn-primary btn-rectangle mt-3" style="color: black; box-shadow: none !important;" onclick="introJs().setOptions({highlightClass: 'custom-highlight', overlayOpacity: 0.5}).start();"><i class="fa fa-play-circle" style=""></i><span>&nbsp;&nbsp;&nbsp;Guide</span></button>

        <div class="row mt-3 d-flex align-items-stretch">

          <div class="col-12 col-md-12 col-lg-12 col-xl-4 d-flex align-items-stretch">

            <div data-step="4" data-intro="Hashtags of people, places, and organizations." class="card w-100 shadow mb-3">
              <!-- Card Header - Dropdown -->
              <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                <h5 class="m-0 font-weight-bold">Hashtags</h5>
              </div>
              <!-- Card Body -->
              <div class="card-body">
                <div id="hashtags">
                  <div class="basement_title mb-2">
                    <span class="h5" id="sm-tags">Google</span>
                      <span id="hash-icons" data-step="5" data-intro="Click an icon to change the platform for the hashtags" style="float:right;height:22px;">
                        <a id="google-link" class="item google-highlight waves-effect waves-light mr-1" href="javascript:void(0)"><em id="google-icon" style="color:var(--brand-color);" class="fab fa-google"></em></a>
                        <a id="youtube-link" class="item youtube-highlight waves-effect waves-light mr-1" href="javascript:void(0)"><em id="youtube-icon" style="color:#34495E;" class="fab fa-youtube"></em></a>
                        <?php /*
                        <a id="twitter-link" class="item twitter-highlight waves-effect waves-light" href="javascript:void(0)"><em id="twitter-icon" style="color:#34495E" class="fab fa-twitter"></em></a>
                        <a id="insta-link" class="item instagram-highlight waves-effect waves-light" href="javascript:void(0)"><em id="insta-icon" style="color:#34495E" class="fab fa-instagram"></em></a>
                        */ ?>
                        <a id="search-link" class="item search-highlight waves-effect waves-light" href="javascript:void(0)"><em id="search-icon" style="color:#34495E;" class="fa fa-search"></em></a>
                      <span>
                  </div>
          
                  <div id="twitter-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                    
                    
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {

                    ?>
                            <div class="hashtag"><a href="https://twitter.com/search?q=%23<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?>" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>" data-label="<?= $arr['entities'][$i]['label'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>                  
                    <?php } ?>

                  </div>

                  <div id="insta-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.instagram.com/explore/tags/<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?>/" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>" data-label="<?= $arr['entities'][$i]['label'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>
                    <?php } ?>

                  </div>

                  <div id="google-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.google.com/search?q=<?=str_replace(' ', '+', $arr['entities'][$i]['text'])?>" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>" data-label="<?= $arr['entities'][$i]['label'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>
                                        
                    <?php } ?>

                  </div>

                  <div id="youtube-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.youtube.com/results?search_query=<?=str_replace(' ', '+', $arr['entities'][$i]['text'])?>" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>" data-label="<?= $arr['entities'][$i]['label'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>
                        
                    <?php } ?>

                  </div>

                  <div id="search-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="search.php?q=<?=str_replace(' ', '+', $arr['entities'][$i]['text'])?>" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>" data-label="<?= $arr['entities'][$i]['label'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>
                        
                    <?php } ?>

                  </div>

                </div>
              </div>

            </div>
          </div>

          <div class="col-12 col-md-12 col-lg-12 col-xl-8 d-flex align-items-stretch">
            <div class="row d-flex align-items-stretch">
                <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch" style="max-height: 340px;">
                    <div data-step="6" data-intro="Wikipedia articles." class="card w-100 shadow mb-3">
                      <!-- Card Header - Dropdown -->
                      <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                        <h5 class="m-0 font-weight-bold">Wikipedia</h5>
                      </div>
                      <!-- Card Body -->
                      <div class="card-body">
                        <div id="wikipedia">
                          <ol>
                            <?php

                             foreach ($arr['entities'] as $entity) {
                              
                                if (array_key_exists("wikipedia_url", $entity)) {
                                  echo '
                                      <li><a href="'.$entity["wikipedia_url"].'" target="_blank" data-label="'.$entity["label"].'">'.$entity["text"].'</a></li>';
                                }    
                              }
                            ?>

                          </ol>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch">
                    <div data-step="7" data-intro="A taxonomy of the topics covered in the article." class="card w-100 shadow mb-3">
                      <!-- Card Header - Dropdown -->
                      <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                        <h5 class="m-0 font-weight-bold">Subject Matter</h5>
                      </div>
                      <!-- Card Body -->
                      <div class="card-body">
                        <div id="subject-matter">
                          <ul class="rec-list" style="padding-left:10px;">

                            <?php
                              // --- 1) Input topics (label => score 0..1) ---
                              $topics = (isset($arr['topics']) && is_array($arr['topics'])) ? $arr['topics'] : [];

                              // Normalize topics to sum to 1.0 (so displayed % add to ~100)
                              $total = 0.0;

                              // detect if values are 0..100 already (optional heuristic)
                              $maxVal = 0.0;
                              foreach ($topics as $lbl => $v) {
                                $v = (float)$v;
                                $maxVal = max($maxVal, $v);
                              }

                              // If values look like 0..100, convert to 0..1 first
                              if ($maxVal > 1.00001) {
                                foreach ($topics as $lbl => $v) {
                                  $topics[$lbl] = ((float)$v) / 100.0;
                                }
                              }

                              foreach ($topics as $lbl => $v) {
                                $total += max(0.0, (float)$v);
                              }

                              if ($total > 0) {
                                foreach ($topics as $lbl => $v) {
                                  $topics[$lbl] = max(0.0, (float)$v) / $total;  // now sums to 1.0
                                }
                              }

                              // --- 2) 4-group taxonomy ---
                              $groupMap = [
                                'Politics & Government' => [
                                  'Political Theatre',
                                  'Government',
                                ],
                                'Society & Public Safety' => [
                                  'Civil Unrest, Conflict',
                                  'Friends and Family',
                                ],
                                'Health & Environment' => [
                                  'Health',
                                  'Environment, Climate',
                                  'Food and Beverage',
                                ],
                                'Business & Technology' => [
                                  'Business, Companies',
                                  'Technology',
                                ],
                                'Culture & Media' => [
                                  'Entertainment',
                                ],
                              ];

                              // --- 3) Build groups with rollups ---
                              $groups = []; // parent => ['total'=>float, 'children'=>[topic=>float]]
                              foreach ($groupMap as $parent => $childrenList) {
                                $groups[$parent] = ['total' => 0.0, 'children' => []];

                                foreach ($childrenList as $topicLabel) {
                                  // find the topic score in $topics by exact key
                                  if (array_key_exists($topicLabel, $topics)) {
                                    $score = (float)$topics[$topicLabel];
                                    $groups[$parent]['children'][$topicLabel] = $score;
                                    $groups[$parent]['total'] += $score;
                                  }
                                }
                              }

                              // --- 4) Handle any "unknown/unmapped" topics gracefully ---
                              $mapped = [];
                              foreach ($groupMap as $parent => $childrenList) {
                                foreach ($childrenList as $t) $mapped[$t] = true;
                              }

                              $unmapped = [];
                              foreach ($topics as $label => $score) {
                                if (!isset($mapped[$label])) {
                                  $unmapped[$label] = (float)$score;
                                }
                              }
                              if (!empty($unmapped)) {
                                $groups['Other'] = [
                                  'total' => array_sum($unmapped),
                                  'children' => $unmapped
                                ];
                              }

                              // --- 5) Sort parents by total desc; children by score desc ---
                              uasort($groups, fn($a,$b) => ($b['total'] <=> $a['total']));
                              foreach ($groups as $p => $g) {
                                arsort($groups[$p]['children']);
                              }

                              // Optional: hide empty groups
                              $groups = array_filter($groups, fn($g) => !empty($g['children']) || ($g['total'] > 0));

                              // --- 6) Render ---
                              foreach ($groups as $parent => $g) {

                                $parentPct = number_format($g['total'] * 100, 2);

                                echo '
                                  <li class="rec-list-head">
                                    <div class="left-view">
                                      <b>' . htmlspecialchars($parent) . '</b>
                                      <span class="right-view"><b>' . $parentPct . '%</b></span>
                                    </div>
                                  </li>
                                ';

                                echo '<ul class="rec-list" style="padding-left:15px;">';

                                foreach ($g['children'] as $childLabel => $childScore) {
                                  $childPct = number_format($childScore * 100, 2);

                                  echo '
                                    <li class="rec-list">
                                      <div class="left-view">
                                        ' . htmlspecialchars($childLabel) . '
                                        <span class="right-view">' . $childPct . '%</span>
                                      </div>
                                    </li>
                                  ';
                                }

                                echo '</ul>';
                              }
                            ?>

                          </ul>
                        </div>
                      </div>
                    </div>

                  </div>
                  <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch">
                    <div data-step="8" data-intro="Emotions evoked." class="card w-100 shadow mb-3">
                      <!-- Card Header - Dropdown -->
                      <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                        <h5 class="m-0 font-weight-bold">Emotional Reaction</h5>
                      </div>
                      <!-- Card Body -->
                      <div class="card-body">
                        <div id="emotions">

                          <?php
                            // Normalize: ensure keys exist (keep your existing defaults)
                            $reaction = [
                              'love'  => 0,
                              'angry' => 0,
                              'ahah'  => 0,
                              'wow'   => 0,
                              'sad'   => 0,
                            ];

                            foreach (($arr['emotional_reaction'] ?? []) as $key => $value) {
                              $k = strtolower(trim((string)$key));
                              if (array_key_exists($k, $reaction)) {
                                $reaction[$k] = (float)$value;
                              }
                            }

                            // If your values are 0..1, convert to percent. If they’re already 0..100, this keeps them sane.
                            $max = max($reaction) ?: 0;

                            $toPct = function($v) use ($max) {
                              $v = (float)$v;
                              // Heuristic: if it looks like 0..1 floats, convert to 0..100
                              if ($v <= 1.00001) $v *= 100.0;
                              // Clamp
                              if ($v < 0) $v = 0;
                              if ($v > 100) $v = 100;
                              return (int)round($v);
                            };

                            // Display order
                            $order = ['love','ahah','wow','sad','angry'];

                            // Optional: pretty labels
                            $labels = [
                              'love'  => 'Love',
                              'ahah'  => 'Aha-ha',
                              'wow'   => 'Wow',
                              'sad'   => 'Sad',
                              'angry' => 'Angry',
                            ];
                          ?>

                          <div class="sn-emo" role="group" aria-label="Emotional Reaction">
                            <?php foreach ($order as $k): ?>
                              <?php $pct = $toPct($reaction[$k] ?? 0); ?>
                              <div class="sn-emo-row" data-emo="<?= htmlspecialchars($k) ?>">
                                <div class="sn-emo-label"><?= htmlspecialchars($labels[$k] ?? ucfirst($k)) ?></div>
                                <div class="sn-emo-bar" style="--v: <?= $pct ?>%;">
                                  <span class="sn-emo-fill"></span>
                                </div>
                                <div class="sn-emo-val"><?= $pct ?>%</div>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch">
                    <div data-step="9" data-intro="Author's tone." class="card w-100 shadow mb-3">
                      <!-- Card Header - Dropdown -->
                      <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                        <h5 class="m-0 font-weight-bold">Sentiment</h5>
                      </div>
                      <!-- Card Body -->
                      <div class="card-body">
                        <div id="sentiment" class="text-center" style="height: 220px;">
                          <?php

                            $arrss = ['positive','neutral','negative'];

                            // Emoji map
                            $emoji = [
                              'positive' => '🙂',
                              'neutral'  => '😐',
                              'negative' => '☹️'
                            ];

                            // $arr should be decoded NLP array
                            $score = null;
                            if (isset($arr['sentiment']) && is_array($arr['sentiment'])) {
                              $score = $arr['sentiment']['score'] ?? null;
                            }

                            // Compute bucket using shared thresholds
                            $bucket = sn_sentiment_bucket_from_score($score); // positive/neutral/negative/unknown

                            // Treat unknown as neutral
                            $sentiment = ($bucket === 'unknown') ? 'neutral' : $bucket;

                            foreach ($arrss as $label) {

                              $ctc = ($label === $sentiment)
                                ? 'style="background:black; padding:7px 10px; color:white; border-radius:6px;"'
                                : '';

                              $icon = $emoji[$label] ?? '';

                              echo '<p class="text-center" style="margin-top:12px;">
                                      <span '.$ctc.'> '.$icon.' '.ucfirst($label).' </span>
                                    </p>';
                            }
                          ?>

                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>

            
        </div>


          