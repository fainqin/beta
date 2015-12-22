<ul>
    <?php foreach ($this->getRecentComments() as $comment): ?>
        <div class="comment_wrapper">
            <div class="content">
                <?php echo CHtml::link(CHtml::encode($comment->content), array('issue/view', 'id' => $comment->issue->id)); ?>
            </div>
            <div class="issue">
                Issue: <?php echo CHtml::link(CHtml::encode($comment->issue->name), array('issue/view', 'id' => $comment->issue->id)); ?>
            </div>
            <div class="author" style="display: inline;">
                By: <?php echo $comment->author->username; ?>
            </div>
            <div class="create_time" style="display: inline;">
                At: <?php echo date('F j, Y \a\t h:i a', strtotime($comment->create_time)); ?>

            </div>
        </div>
    <?php endforeach; ?>
</ul>
