<?php
if ( ! defined( 'ABSPATH' ) )
exit;

$attempt_id = (int) sanitize_text_field($_GET['attempt_id']);


if (!$attempt_id){
    ?>
    <h1><?php _e('Attempt not found', 'tutor'); ?></h1>
    <?php
    return;
}

$user_id = tutor_utils()->get_user_id();
$attempt_data = tutor_utils()->get_attempt($attempt_id);
if ( $user_id != $attempt_data->user_id ){
    ?>
    <h1><?php _e('You have no access.', 'tutor'); ?></h1>
    <?php
    return;
}
$answers = tutor_utils()->get_quiz_answers_by_attempt_id($attempt_id);
?>


<div>
    <?php $attempts_page = tutor_utils()->get_tutor_dashboard_page_permalink('my-quiz-attempts'); ?>
    <a href="<?php echo $attempts_page; ?>"><?php _e('< Back to Attempt List', 'tutor'); ?></a>
</div>


<div class="tutor-quiz-attempt-review-wrap">
    <h5><?php echo __('Quiz:','tutor')." <a href='" .get_permalink($attempt_data->quiz_id)."'>".get_the_title($attempt_data->quiz_id)."</a>"; ?></h5>
    <p><?php echo __('Course:','tutor')." <a href='" .get_permalink($attempt_data->course_id)."'>".get_the_title($attempt_data->course_id)."</a>"; ?></p>
</div>

<table class="wp-list-table">
    <tr>
        <th><?php _e('#', 'tutor'); ?></th>
        <th><?php _e('Attempts Date', 'tutor'); ?></th>
        <th><?php _e('Questions', 'tutor'); ?></th>
        <th><?php _e('Total Marks', 'tutor'); ?></th>
        <th><?php _e('Pass Marks', 'tutor'); ?></th>
        <th><?php _e('Correct', 'tutor'); ?></th>
        <th><?php _e('Incorrect', 'tutor'); ?></th>
        <th><?php _e('Earned Marks', 'tutor'); ?></th>
        <th><?php _e('Grade', 'tutor'); ?></th>
        <th><?php _e('Results', 'tutor'); ?></th>
    </tr>
    
    <tr>
        <td><?php echo $attempt_data->attempt_id; ?></td>
        <td>
            <?php
                echo date_i18n(get_option('date_format'), strtotime($attempt_data->attempt_started_at)).' '.date_i18n(get_option('time_format'), strtotime($attempt_data->attempt_started_at));
            ?>
        </td>
        <td><?php echo $attempt_data->total_questions; ?></td>
        <td><?php echo $attempt_data->total_marks; ?></td>
        <td>
            <?php 
                $pass_mark_percent = tutor_utils()->get_quiz_option($attempt_data->quiz_id, 'passing_grade', 0);
                echo $pass_mark_percent.'%';
            ?>
        </td>
        <td>
            <?php
            $correct = 0;
            $incorrect = 0;
            if(is_array($answers) && count($answers) > 0) {
                foreach ($answers as $answer){
                    if ( (bool) isset( $answer->is_correct ) ? $answer->is_correct : '' ) {
                        $correct++;
                    } else {
                        if ($answer->question_type === 'open_ended' || $answer->question_type === 'short_answer'){
                        } else {
                            $incorrect++;
                        }
                    }
                }
            }
            echo $correct;
            ?>
        </td>
        <td><?php echo $incorrect; ?></td>
        <td><?php echo $attempt_data->earned_marks; ?></td>
        <td>
            <?php 
                $earned_percentage = $attempt_data->earned_marks > 0 ? ( number_format(($attempt_data->earned_marks * 100) / $attempt_data->total_marks)) : 0;
                echo $earned_percentage.'%';
            ?>
        </td>
        <td>
            <?php 
                if ($earned_percentage >= $pass_mark_percent){
                    echo '<span class="result-pass">'.__('Pass', 'tutor').'</span>';
                }else{
                    echo '<span class="result-fail">'.__('Fail', 'tutor').'</span>';
                }
            ?>
        </td>
    </tr>
</table>


<div class="tutor-quiz-attempt-review-wrap">
    <?php
    if (is_array($answers) && count($answers)){

        ?>
        <div class="quiz-attempt-answers-wrap">

            <div class="attempt-answers-header">
                <h3><?php _e('Quiz Overview', 'tutor'); ?></h3>
            </div>

            <table class="wp-list-table">
                <tr>
                    <th><?php _e('Type', 'tutor'); ?></th>
                    <th><?php _e('No.', 'tutor'); ?></th>
                    <th><?php _e('Question', 'tutor'); ?></th>
                    <th><?php _e('Given Answers', 'tutor'); ?></th>
                    <th><?php _e('Correct/Incorrect', 'tutor'); ?></th>
                </tr>
                <?php
                $answer_i = 0;
                foreach ($answers as $answer){
                    $answer_i++;
                    $question_type = tutor_utils()->get_question_types($answer->question_type);
                    ?>
                    <tr>
                        <td><?php echo $question_type['icon']; ?></td>
                        <td><?php echo $answer_i; ?></td>
                        <td><?php echo stripslashes($answer->question_title); ?></td>
                        <td>
                            <?php
                            if ($answer->question_type === 'true_false' || $answer->question_type === 'single_choice' ){
                                $get_answers = tutor_utils()->get_answer_by_id($answer->given_answer);
                                $answer_titles = wp_list_pluck($get_answers, 'answer_title');
                                $answer_titles = array_map('stripslashes', $answer_titles);
                                echo '<p>'.implode('</p><p>', $answer_titles).'</p>';
                            }elseif ($answer->question_type === 'multiple_choice'){
                                $get_answers = tutor_utils()->get_answer_by_id(maybe_unserialize($answer->given_answer));
                                $answer_titles = wp_list_pluck($get_answers, 'answer_title');
                                $answer_titles = array_map('stripslashes', $answer_titles);
                                echo '<p>'.implode('</p><p>', $answer_titles).'</p>';
                            }elseif ($answer->question_type === 'fill_in_the_blank'){
                                $answer_titles = maybe_unserialize($answer->given_answer);
                                $get_db_answers_by_question = tutor_utils()->get_answers_by_quiz_question($answer->question_id);
                                foreach ($get_db_answers_by_question as $db_answer);
                                $count_dash_fields = substr_count($db_answer->answer_title, '{dash}');
                                if ($count_dash_fields){
                                    $dash_string = array();
                                    $input_data = array();
                                    for($i=0; $i<$count_dash_fields; $i++){
                                        //$dash_string[] = '{dash}';
                                        $input_data[] =  isset($answer_titles[$i]) ? "<span class='filled_dash_unser'>{$answer_titles[$i]}</span>" : "______";
                                    }
                                    $answer_title = $db_answer->answer_title;
                                    foreach($input_data as $replace){
                                        $answer_title = preg_replace('/{dash}/i', $replace, $answer_title, 1);
                                    }
                                    echo str_replace('{dash}', '_____', $answer_title);
                                }

                            }elseif ($answer->question_type === 'open_ended' || $answer->question_type === 'short_answer'){

                                if ($answer->given_answer){
                                    echo wpautop(stripslashes($answer->given_answer));
                                }

                            }elseif ($answer->question_type === 'ordering'){

                                $ordering_ids = maybe_unserialize($answer->given_answer);
                                foreach ($ordering_ids as $ordering_id){
                                    $get_answers = tutor_utils()->get_answer_by_id($ordering_id);
                                    $answer_titles = wp_list_pluck($get_answers, 'answer_title');
                                    $answer_titles = array_map('stripslashes', $answer_titles);
                                    echo '<p>'.implode('</p><p>', $answer_titles).'</p>';
                                }

                            }elseif ($answer->question_type === 'matching'){

                                $ordering_ids = maybe_unserialize($answer->given_answer);
                                $original_saved_answers = tutor_utils()->get_answers_by_quiz_question($answer->question_id);

                                foreach ($original_saved_answers as $key => $original_saved_answer){
                                    $provided_answer_order_id = isset($ordering_ids[$key]) ? $ordering_ids[$key] : 0;
                                    $provided_answer_order = tutor_utils()->get_answer_by_id($provided_answer_order_id);
                                    if(tutils()->count($provided_answer_order)){
                                        foreach ($provided_answer_order as $provided_answer_order);
                                        echo $original_saved_answer->answer_title  ." - {$provided_answer_order->answer_two_gap_match} <br />";
                                    }
                                }

                            }elseif ($answer->question_type === 'image_matching'){

                                $ordering_ids = maybe_unserialize($answer->given_answer);
                                $original_saved_answers = tutor_utils()->get_answers_by_quiz_question($answer->question_id);

                                echo '<div class="answer-image-matched-wrap">';
                                foreach ($original_saved_answers as $key => $original_saved_answer){
                                    $provided_answer_order_id = isset($ordering_ids[$key]) ? $ordering_ids[$key] : 0;
                                    $provided_answer_order = tutor_utils()->get_answer_by_id($provided_answer_order_id);
                                    foreach ($provided_answer_order as $provided_answer_order);
                                    ?>
                                    <div class="image-matching-item">
                                        <p class="dragged-img-rap"><img src="<?php echo wp_get_attachment_image_url( $original_saved_answer->image_id); ?>" /> </p>
                                        <p class="dragged-caption"><?php echo $provided_answer_order->answer_title; ?></p>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            }elseif ($answer->question_type === 'image_answering'){

                                $ordering_ids = maybe_unserialize($answer->given_answer);

                                echo '<div class="answer-image-matched-wrap">';
                                foreach ($ordering_ids as $answer_id => $image_answer){
                                    $db_answers = tutor_utils()->get_answer_by_id($answer_id);
                                    foreach ($db_answers as $db_answer);
                                    ?>
                                    <div class="image-matching-item">
                                        <p class="dragged-img-rap"><img src="<?php echo wp_get_attachment_image_url( $db_answer->image_id); ?>" /> </p>
                                        <p class="dragged-caption"><?php echo $image_answer; ?></p>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            }

                            ?>
                        </td>

                        <td>
                            <?php

                            if ( (bool) isset( $answer->is_correct ) ? $answer->is_correct : '' ) {
                                echo '<span class="quiz-correct-answer-text"><i class="tutor-icon-mark"></i> '.__('Correct', 'tutor').'</span>';
                            } else {
                                if ($answer->question_type === 'open_ended' || $answer->question_type === 'short_answer'){
                                    echo '<p style="color: #878A8F;"><span style="color: #ff282a;">&ast;</span> '.__('Review Required', 'tutor').'</p>';
                                }else {
                                    echo '<span class="quiz-incorrect-answer-text"><i class="tutor-icon-line-cross"></i> '.__('Incorrect', 'tutor').'</span>';
                                }
                            }
                            ?>
                        </td>

                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>

        <?php
    }
    ?>
</div>