<?php

class ProjectController extends Controller {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * Controllers: This rule specifies an array of controller IDs to which the rule should apply.
     * Roles: This rule specifies a list of authorization items (roles, operation, permissions) to which the rule applies. This makes used of the RBAC feature we will be discussing in the next section.
     * Ips: This rule specifies a list of client IP addresses to which this rule applies.
     * Verbs: This rule specifies which HTTP request types (GET, POST, and so on) apply to this rule.
     * Expression: This rule specifies a PHP expression whose value indicates whether or not the rule should be applied.
     * Actions: This rule specifies the action method, by use of the corresponding action ID, to which the rule should match.
     * Users: This rule specifies the users to which the rule should apply. The current application user's name attribute is used for matching. Three special characters can also be used here:
     * : any user
      ?: anonymous users
      @: authenticated users
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array('index', 'view', 'adduser'),
                'users' => array('@'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('create', 'update'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete'),
                'users' => array('admin'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $issueDataProvider = new CActiveDataProvider('Issue', array(
                    'criteria' => array(
                        'condition' => 'project_id=:projectId',
                        'params' => array(':projectId' => $this->loadModel($id)->id),
                    ),
                    'pagination' => array(
                        'pageSize' => 1,
                    ),
                ));

        $this->render('view', array(
            'model' => $this->loadModel($id),
            'issueDataProvider' => $issueDataProvider,
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $model = new Project;

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Project'])) {
            $model->attributes = $_POST['Project'];

            if ($model->save()) {
                // Set project creator as owner.
                $user = User::model()->findByPk($model->create_user_id);
                $model->associateUserToProject($user);
                $model->associateUserToRole('owner', $user->id);

                $this->redirect(array('view', 'id' => $model->id));
            }
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Project'])) {
            $model->attributes = $_POST['Project'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->id));
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        if (Yii::app()->request->isPostRequest) {
            // we only allow deletion via POST request
            $this->loadModel($id)->delete();

            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if (!isset($_GET['ajax']))
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
        }
        else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Project');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new Project('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Project']))
            $model->attributes = $_GET['Project'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer the ID of the model to be loaded
     */
    public function loadModel($id) {
        $model = Project::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param CModel the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'project-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function actionAdduser($id) {
        $project = $this->loadModel($id);
        if (!Yii::app()->user->checkAccess('createUser', array('project' => $project))) {
            throw new CHttpException(403, 'You are not authorized to perform this action.');
        }
        $form = new ProjectUserForm;
        // collect user input data
        if (isset($_POST['ProjectUserForm'])) {
            $form->attributes = $_POST['ProjectUserForm'];
            $form->project = $project;
            // validate user input and set a sucessfull flassh message if valid
            if ($form->validate()) {
                Yii::app()->user->setFlash('success', $form->username . " has been added to the project.");
                $form = new ProjectUserForm;
            }
        }
        // display the add user form
        $users = User::model()->findAll();
        $usernames = array();
        foreach ($users as $user) {
            $usernames[] = $user->username;
        }
        $form->project = $project;
        $this->render('adduser', array('model' => $form, 'usernames' => $usernames));
    }

}
