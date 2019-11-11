<?php
ini_set('max_execution_time', 36000);
include_once('simple_html_dom.php');

$dbh = new PDO('mysql:host=localhost;dbname=crossword', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//$questions = []; // 'id' 'name'  'id_A => []'
//$answers = []; // 'id' 'name' 'length' 'id_Q => []'
//$j = 0; //id_answer
//$k = 0; //id_question
//$currentAnswer = 0;
//$currentQuestion = 0;
//$uniqueQuestion = true;

function getQuestionId($name)
{
    global $dbh;
    $sql = "SELECT id FROM question WHERE name = :name ";
    $statement = $dbh->prepare($sql);
    $statement->bindParam(':name', $name);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    return ($result ? $result['id'] : 0);
}

function addQuestion($name)
{
    global $dbh;
    try {
        $sql = "INSERT INTO question (name) VALUES (:name) ";
        $statement = $dbh->prepare($sql);
        $statement->bindParam(':name', $name);
        $statement->execute();
    } catch (Exeption $e) {
        die('Whoops, something went wrong.');
    }
}

function getAnswerId($name)
{
    global $dbh;
    $sql = "SELECT id FROM answer WHERE name = :name ";
    $statement = $dbh->prepare($sql);
    $statement->bindParam(':name', $name);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    return ($result ? $result['id'] : 0);
}

function addAnswer($name, $length)
{
    global $dbh;
    $sql = "INSERT INTO answer (name, length) VALUES (:name, :length)";
    $statement = $dbh->prepare($sql);
    $statement->bindParam(':name', $name);
    $statement->bindParam(':length', $length);
    $statement->execute();
}

function addMap($questionId, $answerId)
{
    global $dbh;
    $sql = "INSERT IGNORE INTO question_answer SET id_question = :questionId, id_answer = :answerId ";
    $statement = $dbh->prepare($sql);
    $statement->bindParam(':questionId', $questionId);
    $statement->bindParam(':answerId', $answerId);
    $statement->execute();

}

$html = file_get_html('https://www.kreuzwort-raetsel.net/uebersicht.html');
$letters = $html->find('ul.dnrg > li');
foreach($letters as $letter) {
    $linkLetter = $letter->find('a');
    $fullLinkLetter = 'https://www.kreuzwort-raetsel.net/' . $linkLetter[0]->href;
    echo 'Current letter url: '.$fullLinkLetter.'<br>';

    $currentLetterContent = file_get_html($fullLinkLetter);
    $pages = $currentLetterContent->find('ul.dnrg > li');
    foreach($pages as $page) {
        $linkPage = $page->find('a');
        $fullLinkPage = 'https://www.kreuzwort-raetsel.net/' . $linkPage[0]->href;
        echo 'Current page url: '.$fullLinkPage.'<br>';

        $currentQuestionContent = file_get_html($fullLinkPage);
        $questions = $currentQuestionContent->find('tbody > tr > td.Question');

        foreach($questions as $question) {
            $questionId = getQuestionId($question->plaintext);
            if (!$questionId) {
                addQuestion($question->plaintext);
                $questionId = getQuestionId($question->plaintext);
                $linkQuestion = $question->find('a');
                $fullLinkQuestion = 'https://www.kreuzwort-raetsel.net/' . $linkQuestion[0]->href;
                echo 'Current question url: '.$fullLinkQuestion.'<br>';

                $currentQustionContent = file_get_html($fullLinkQuestion);

                $answers = $currentQustionContent->find('tbody > tr');

                foreach ($answers as $answer) {
                    $answerLength = $answer->find('td.Length');
                    $answerName = $answer->find('td.Answer');

                    if (!($answerId = getAnswerId($answerName[0]->plaintext))) {
                        addAnswer($answerName[0]->plaintext, $answerLength[0]->plaintext);
                        $answerId = getAnswerId($answerName[0]->plaintext);
                    }
                    addMap($questionId, $answerId);
                }
            }
        }
    }
    exit;
}