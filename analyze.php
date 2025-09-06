        <?php
          error_reporting(E_ERROR | E_PARSE);

          set_time_limit(300);
  
          require_once('___modules.php');

          $url = trim($_POST['url'] ?? $_GET['url'] ?? '');

          // get analytics
          $arr = azeo_site_results($url);


          if ($arr["error"] == "No features in text.") {
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
        

        <button class="btn btn-small btn-primary btn-rectangle mt-3" style="color: black; box-shadow: none !important;" onclick="introJs().setOptions({highlightClass: 'custom-highlight', overlayOpacity: 0.5}).start();"><i class="fa fa-play-circle" style=""></i><span>&nbsp;&nbsp;&nbsp;Guide</span></button>

        <div class="row mt-3 d-flex align-items-stretch">

          <div class="col-12 col-md-12 col-lg-12 col-xl-4 d-flex align-items-stretch">

            <div data-step="4" data-intro="Hashtags of people, places, and organizations." class="card shadow w-100 mb-4">
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
                        <a id="google-link" class="item google-highlight waves-effect waves-light" href="javascript:void(0)"><em id="google-icon" style="color:var(--brand-color);" class="fa fa-search"></em></a>
                        <a id="youtube-link" class="item youtube-highlight waves-effect waves-light" href="javascript:void(0)"><em id="youtube-icon" style="color:#34495E" class="fab fa-youtube"></em></a>
                        <a id="twitter-link" class="item twitter-highlight waves-effect waves-light" href="javascript:void(0)"><em id="twitter-icon" style="color:#34495E" class="fab fa-twitter"></em></a>
                        <a id="insta-link" class="item instagram-highlight waves-effect waves-light" href="javascript:void(0)"><em id="insta-icon" style="color:#34495E" class="fab fa-instagram"></em></a>
                      <span>
                  </div>
          
                  <div id="twitter-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                    
                    
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {

                    ?>
                            <div class="hashtag"><a href="https://twitter.com/search?q=%23<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?>" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>                  
                    <?php } ?>

                  </div>

                  <div id="insta-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.instagram.com/explore/tags/<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?>/" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>
                    <?php } ?>

                  </div>

                  <div id="google-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.google.com/search?q=<?=str_replace(' ', '+', $arr['entities'][$i]['text'])?>" target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>
                                        
                    <?php } ?>

                  </div>

                  <div id="youtube-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.youtube.com/results?search_query=<?=str_replace(' ', '+', $arr['entities'][$i]['text'])?>"  target="_blank" data-hashtext="<?= $arr['entities'][$i]['text'] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]['text']))?></a></div>
                        
                    <?php } ?>

                  </div>

                </div>
              </div>

            </div>
          </div>

          <div class="col-12 col-md-12 col-lg-12 col-xl-8 d-flex align-items-stretch">
            <div class="row d-flex align-items-stretch">
                <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch" style="max-height: 340px;">
                    <div data-step="6" data-intro="Wikipedia articles." class="card w-100 shadow mb-4">
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
                                      <li><a href="'.$entity["wikipedia_url"].'" target="_blank">'.$entity["text"].'</a></li>';
                                }    
                              }
                            ?>

                          </ol>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch">
                    <div data-step="7" data-intro="A taxonomy of the topics covered in the article." class="card w-100 shadow mb-4">
                      <!-- Card Header - Dropdown -->
                      <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                        <h5 class="m-0 font-weight-bold">Subject Matter</h5>
                      </div>
                      <!-- Card Body -->
                      <div class="card-body">
                        <div id="subject-matter">
                          <ul class="rec-list" style="padding-left:10px;">
                               
                            <?php

                            foreach ($arr['topics'] as $key => $value) {
                            

                            echo ' 
                                <li class="rec-list-head">
                              
                                  <div class="left-view"> '.$key.' 
                                
                                    <span class="right-view">  <b> '.number_format($value * 100,2).'%  </b></span>
                                
                                </div>

                                </li>';
                                
                                
                                /*

                                $ds=$arr['topics'][$i]['sublevels'];
                                
                               
                               
                               
                                if(count($ds) > 0)
                                {
                                    echo '<ul  class="rec-list"style="padding-left:15px;">';
                                    
                                for($j=0;$j<count($ds);$j++)
                                {
                                    echo '
                                    
                                     <li class="rec-list">
                              
                                  <div class="left-view"> '.ucfirst($ds[$j]['category']).'
                                
                                    <span class="right-view"> '.number_format($ds[$j]['score'] * 100,2).'% </span>
                                
                                </div>

                                     </li>
                                    
                                    ';
                                    
                                }
                                echo '</ul>';
                                }

                                */
                                
                                
                              }                   
                              
                              ?>
                                              
                            </ul>
                        </div>
                      </div>
                    </div>

                  </div>
                  <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch">
                    <div data-step="8" data-intro="Emotions evoked." class="card w-100 shadow mb-4">
                      <!-- Card Header - Dropdown -->
                      <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                        <h5 class="m-0 font-weight-bold">Emotional Reaction</h5>
                      </div>
                      <!-- Card Body -->
                      <div class="card-body">
                        <div id="emotions">

                          <?php

                            $reaction['love']=0;
                            $reaction['angry']=0;
                            $reaction['ahah']=0;
                            $reaction['wow']=0;
                            $reaction['sad']=0;

                            foreach ($arr['emotional_reaction'] as $key => $value) {
                                
                                $reaction[strtolower($key)]=$value;
                                
                            }
                          ?>

                          <div id="chartdiv" style="height: 290px;"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-md-12 col-lg-12 col-xl-6 d-flex align-items-stretch">
                    <div data-step="9" data-intro="Author's tone." class="card w-100 shadow mb-4">
                      <!-- Card Header - Dropdown -->
                      <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                        <h5 class="m-0 font-weight-bold">Sentiment</h5>
                      </div>
                      <!-- Card Body -->
                      <div class="card-body">
                        <div id="sentiment" class="text-center" style="height: 220px;">
                          <?php
                           
                           
                            $arrss=array('positive','neutral','negative');
                            $sentiment_score = $arr['sentiment'];
                            $sentiment = 'neutral';

                            if ($sentiment_score < -0.025) {
                              $sentiment = 'negative';
                            }
                            else if ($sentiment_score > 0.025) {
                              $sentiment = 'positive';
                            }
                          
                            foreach($arrss as $arrsss)
                            {
                              $ctc='';
                              if($arrsss == $sentiment)
                                $ctc='style="background:black; padding:7px 10px; color:white;"';
                              
                              echo  '<p class="text-center" style="margin-top:40px; "><span '.$ctc.'> '.ucfirst($arrsss).' </span> </p>';   
              
                            }
                          
                          ?>

                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>

            
        </div>


        <script>
            var chart = AmCharts.makeChart( "chartdiv", {
              "type": "serial",
              "theme": "light",
              "dataProvider": [ {
                "country": "love",
                "visits": <?= $reaction['love'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "angry",
                "visits": <?= $reaction['angry'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "ahah",
                "visits": <?= $reaction['ahah'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "wow",
                "visits": <?= $reaction['wow'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "sad",
                "visits": <?= $reaction['sad'] ?>,
                "color": "#00bfa6"
              }
              ],



              "valueAxes": [ {
                "gridColor": "#FFFFFF",
                "gridAlpha": 0.2,
                "dashLength": 0
              } ],
              "gridAboveGraphs": true,
              "startDuration": 1,
              "graphs": [ {
                "balloonText": "[[category]]: <b>[[value]]</b>",
                "fillAlphas": 0.8,
                "lineAlpha": 0.2,
              "fillColorsField": "color",
                "type": "column",
                "valueField": "visits"
              } ],
              "chartCursor": {
                "categoryBalloonEnabled": false,
                "cursorAlpha": 0,
                "zoomable": false
              },
              "categoryField": "country",
              "categoryAxis": {
                "gridPosition": "start",
                "labelRotation": 45,
                "tickPosition": "start",
                
              },
              "export": {
                "enabled": true
              }

            } );
          </script>


          <?php
            function render_empty_analytics(string $url, string $reason = 'No features in text.') {
              $host = parse_url($url, PHP_URL_HOST) ?: 'this page';
              // Small, in-panel empty state card
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
                    We couldn’t find enough readable text on <span class="fw-semibold">{$host}</span> to compute keywords, entities, topics, or sentiment.
                  </p>
                  <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary mr-2" href="{$url}" target="_blank" rel="noopener">Open article</a>
                    <button class="btn btn-sm btn-primary" onclick="reanalyzeAnalytics('{$url}')">Retry</button>
                  </div>
                  <details class="mt-2 small text-muted">
                    <summary class="pointer">Why?</summary>
                    <ul class="mb-0 ps-3">
                      <li>Video/live page or gallery</li>
                      <li>Very short post or headline-only</li>
                      <li>Paywall or script-rendered content</li>
                    </ul>
                  </details>
                </div>
              </div>
            </div>
            HTML;
            }

            ?>