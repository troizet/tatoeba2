<?php
$this->Html->script('/js/sentences/navigation.ctrl.js', ['block' => 'scriptBottom']);
$langArray = $this->Languages->languagesArrayAlone();
$selectedLanguage = $this->request->getSession()->read('random_lang_selected');
if (!$selectedLanguage) {
    $selectedLanguage = 'und';
}
$currentId = $currentId ? $currentId : 'null';
$prev = $prev ? $prev : 'null';
$next = $next ? $next : 'null';
$sentenceUrl = $this->Url->build([
    'controller' => 'sentences',
    'action' => 'show'
]);
?>
<div ng-app="app" ng-controller="SentencesNavigationController as vm" 
     ng-init="vm.init(<?= $selectedLanguage ?>, <?= $currentId ?>, <?= $prev ?>, <?= $next ?>)" 
     class="navigation" layout="row" ng-cloak>

    <div layout="row" layout-align="space-around center" layout-margin flex>
        <md-button ng-href="<?= $sentenceUrl ?>/{{vm.prev}}" class="md-primary"
                   ng-disabled="!vm.prev">
            <md-icon>keyboard_arrow_left</md-icon>
            <?= __('previous') ?>
        </md-button>

        <md-button ng-href="<?= $sentenceUrl ?>/{{vm.lang}}" class="md-primary">
            <?= __('random') ?>
        </md-button>

        <md-button ng-href="<?= $sentenceUrl ?>/{{vm.next}}" class="md-primary"
                   ng-disabled="!vm.next">
            <?= __('next') ?>
            <md-icon>keyboard_arrow_right</md-icon>
        </md-button>

        <div>
        <md-tooltip>
            <?= __('Language for previous, next or random sentence'); ?>
        </md-tooltip>
        <?php
        echo $this->element(
            'language_dropdown', 
            array(
                'name' => 'lang',
                'selectedLanguage' => $selectedLanguage,
                'languages' => $this->Search->getLangs()
            )
        );
        ?>
        </div>
    </div>

    <?php
    // go to form
    echo $this->Form->create('Sentence', [
        'id' => 'go-to-form',
        'url' => ['action' => 'go_to_sentence'],
        'type' => 'get',
        'layout' => 'row',
        'layout-align' => 'center center'
    ]);
    ?>
    <md-input-container layout="row" layout-align="start center">
        <?php
        echo $this->Form->input('sentence_id', [
            'type' => 'text',
            'label' => __('Show sentence #: '),
            'value' => $currentId,
            'lang' => '',
            'dir' => 'ltr',
        ]);
        ?>
        <md-button type="submit" class="go-button">
            <md-icon>arrow_forward</md-icon>
        </md-button>
    </md-input-container>
    <?php
    echo $this->Form->end();
    ?>
</div>