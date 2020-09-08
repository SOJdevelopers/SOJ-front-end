<div id="standings"></div>
<script type="text/javascript">
contest_id = <?= $contest['id'] ?>;
standings = <?= json_encode($standings) ?>;
score = <?= json_encode($score) ?>;
problems = <?= json_encode($contest_data['problems']) ?>;
full_scores = <?= json_encode($contest_data['full_scores']) ?>;
<?= isset($contest['extra_config']['standings_renderer_public']) ? $contest['extra_config']['standings_renderer_public'] : 'showStandings(getUserWithoutLink, true)' ?>;
</script>
