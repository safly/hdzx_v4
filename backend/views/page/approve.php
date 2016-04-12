<?php

/* @var $this yii\web\View */
use backend\assets\ReactAsset;

ReactAsset::register($this);
$this->registerJsFile('@web/js/approve.js', ['depends'=>[ReactAsset::className()]]);
$this->title = '预约审批';
?>
<div id="approve-page">
</div>
<script>
	_Server_Data_.apprveType = '<?= $apprveType ?>';
	_Server_Data_.start_date = '<?= $start_date ?>';
	_Server_Data_.end_date = '<?= $end_date ?>';
</script>