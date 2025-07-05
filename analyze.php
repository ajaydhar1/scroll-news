        <?php
          error_reporting(E_ERROR | E_PARSE);

          set_time_limit(300);
  
          require_once('___modules.php');

          // get analytics
          $arr = azeo_site_results($_POST['url']);

          // Example: If NLP or screenshot fails
          if (empty($arr)) {
              header("Location: index.php?url=" . urlencode($_POST['url']) . "&error=1");
              exit;
          }

        ?>
        

        <button class="btn btn-small btn-primary btn-rectangle mt-3 ml-3" style="color: black;" onclick="introJs().setOptions({highlightClass: 'custom-highlight', overlayOpacity: 0.5}).start();"><i class="fa fa-play-circle" style=""></i><span>&nbsp;&nbsp;&nbsp;Guide</span></button>

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
                  <div class="basement_title">
                    <span class="h5" id="sm-tags">Google</span>
                      <span id="hash-icons" data-step="5" data-intro="Click an icon to change the platform for the hashtags" style="float:right;height:22px;">
                        <a id="google-link" class="item google-highlight waves-effect waves-light" href="javascript:void(0)"><em id="google-icon" style="color:var(--link-color);" class="fa fa-search"></em></a>
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
                            <div class="hashtag"><a href="https://twitter.com/search?q=%23<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]))?>" target="_blank" data-hashtext="<?= $arr['entities'][$i] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]))?></a></div>                  
                    <?php } ?>

                  </div>

                  <div id="insta-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.instagram.com/explore/tags/<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]))?>/" target="_blank" data-hashtext="<?= $arr['entities'][$i] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]))?></a></div>
                    <?php } ?>

                  </div>

                  <div id="google-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.google.com/search?q=<?=str_replace(' ', '+', $arr['entities'][$i])?>" target="_blank" data-hashtext="<?= $arr['entities'][$i] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]))?></a></div>
                                        
                    <?php } ?>

                  </div>

                  <div id="youtube-tags" class="tag-cloud-sidebar basement_content card-content" style="overflow:auto;display:none;">
                        
                    <?php 
                        for($i=0;$i<count($arr['entities']);$i++)
                        {
                    ?>
                            <div class="hashtag"><a href="https://www.youtube.com/results?search_query=<?=str_replace(' ', '+', $arr['entities'][$i])?>"  target="_blank" data-hashtext="<?= $arr['entities'][$i] ?>">#<?=preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $arr['entities'][$i]))?></a></div>
                        
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
                        <h5 class="m-0 font-weight-bold">Wikipedia</h5>
                        <div id="wikipedia">
                          <ol>
                            <?php
            
                             $wiki = $arr['wikipedia'];
                              
                             foreach ($wiki as $key => $value) {
                              
                                echo '
                                      <li><a href="'.$value.'" target="_blank">'.$key.'</a></li>';
                                      
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
                              
                                  <div class="left-view"> <b> '.$key.'  </b>
                                
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

                            /*
                            $react['wow']=0;
                            $react['ahah']=0;
                            $react['angry']=0;
                            $react['love']=0;
                            $react['sad']=0;
                            */

                            $reaction['angry']=0;
                            //$reaction['curious']=0;
                            $reaction['happy']=0;
                            $reaction['inspired']=0;
                            //$reaction['relaxed']=0;
                            $reaction['satisfied']=0;
                            $reaction['unsure']=0;
                            //$reaction['urgency']=0;

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
                "country": "angry",
                "visits": <?= $reaction['angry'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "inspired",
                "visits": <?= $reaction['inspired'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "satisfied",
                "visits": <?= $reaction['satisfied'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "happy",
                "visits": <?= $reaction['happy'] ?>,
                "color": "#00bfa6"
              },
              {
                "country": "unsure",
                "visits": <?= $reaction['unsure'] ?>,
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


<script>

  <?php

                $entityStr = '';
                for($i=0;$i<count($arr['entities']);$i++) {
                    $entityStr = $entityStr.$arr['entities'][$i].'---';
                }
        
                $entityStr = substr($entityStr, 0, -3);


                echo '

                    myArray = "'.str_replace('"','',preg_replace( "/\r|\n/", "", ($entityStr))).'";

                    ';

  ?>

</script>