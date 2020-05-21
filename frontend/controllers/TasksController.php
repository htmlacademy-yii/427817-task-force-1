<?php

namespace frontend\controllers;

use Yii;
use frontend\models\Tasks;
use frontend\models\Users;
use frontend\models\Categories;
use frontend\models\Responds;
use frontend\models\forms\TaskSearchForm;
use frontend\models\forms\TaskCreateForm;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use frontend\models\TaskActions;

class TasksController extends \frontend\controllers\SecuredController
{
    private const CARDS_AMOUNT = 5;

    // закрыть исполнителям доступ к созданию заданий
    public function behaviors()
    {
        $rules = parent::behaviors();
        $rule = [
            'allow' => false,
            'actions' => ['create'],
            'matchCallback' => function ($rule, $action) {
                return Users::isUserDoer(Yii::$app->user->id);
            },
            'denyCallback' => function ($rule, $action) {
                throw new ForbiddenHttpException('Извините, только заказчики могут создавать задачи');
            },
        ];
        array_unshift($rules['access']['rules'], $rule);

        return $rules;
    }

    /**
     * выводит список последних задач.
     *
     * @throws NotFoundHttpException
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $categories = Categories::getList();
        if (!$categories) {
            throw new NotFoundHttpException('не найдено ни одной категории');
        }

        $form = new TaskSearchForm();
        // стартовый запрос
        $query = Tasks::getMainList();

        // добавляются условия из формы
        if (Yii::$app->request->getIsPost()) {
            if ($form->load(Yii::$app->request->post()) && $form->validate()) {
                $form->search($query);
            }
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['dt_add' => SORT_DESC]],
            'pagination' => [
                'pageSize' => self::CARDS_AMOUNT,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'categories' => $categories,
            'model' => $form
        ]);
    }

    /**
     * показывает карточку конкретного задания.
     *
     * @param int $id id задания
     *
     * @throws NotFoundHttpException
     *
     * @return mixed
     */
    public function actionView(int $id)
    {
        $task = Tasks::findOne($id);
        if (!$task) {
            throw new NotFoundHttpException("задание не найдено");
        } elseif (   // гости могут видеть только свободные задачи
            $task->status !== Tasks::STATUS_NEW &&
            !$task->isUserCustomer() &&
            $task->executor->id !== Yii::$app->user->id) {
            throw new NotFoundHttpException('поиск исполнителей завершен');
        }

        // отклики может видеть только заказчик
        $responds = ($task->isUserCustomer()) ? $task->responds : [];
        // контактное лицо в блоке сообщений
        $user = ($task->isUserCustomer() && $task->status !== Tasks::STATUS_NEW) ? $task->executor : $task->author;
        // Доступные гостю действия
        $actions = (new TaskActions($task, Yii::$app->user->id))->getActionList();

        return $this->render('view', ['task' => $task,
        'actions' => $actions,
        'category' => Categories::findOne($task->category_id),
        'customer' => $user,
        'responds' => $responds,
        'attachments' => $task->attachments, ]);
    }

    /**
     * страница с формой нового задания.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $form = new TaskCreateForm();

        if (Yii::$app->request->getIsPost() && $form->load(Yii::$app->request->post())) {
            if ($form->validate() && $form->createTask()) {
                return $this->goHome();
            }
        }

        return $this->render('create', ['categories' => Categories::getList(), 'model' => $form]);
    }

    /**
     * одобрить отклик.
     *
     * @param int $id id отклика
     * @throws NotFoundHttpException
     * @return mixed
     */
    public function actionAgree(int $id)
    {
        $respond = Responds::findOne($id);
        if (!$respond) {
            throw new NotFoundHttpException("отклик не найден");
        }
        $task = new TaskActions($respond->task, Yii::$app->user->id);
        $task->admitRespond($respond);

        return $this->refresh();
    }

    /**
     * отклонить отклик.
     *
     * @param int $id id отклика
     * @throws NotFoundHttpException
     * @return mixed
     */
    public function actionIgnore(int $id)
    {
        $respond = Responds::findOne($id);
        if (!$respond) {
            throw new NotFoundHttpException("отклик не найден");
        }
        $task = new TaskActions($respond->task, Yii::$app->user->id);
        $task->refuseRespond($respond);

        return $this->refresh();
    }

    /**
     * гость откликается.
     *
     * @param int $id id задания
     *
     * @return mixed
     */
    public function actionRespond($id)
    {
        if (Yii::$app->request->getIsPost()) {
            $attributes = Yii::$app->request->post('RenderForm');
            $task = new TaskActions(Tasks::findOne($id), Yii::$app->user->id);
            $task->respond($attributes, Yii::$app->user->id);
        }
        return $this->refresh();
    }

    /**
     * заказчик завершает задание.
     *
     * @param int $id id задания
     *
     * @return mixed
     */
    public function actionComplete($id)
    {
        if (Yii::$app->request->getIsPost()) {
            $attributes = Yii::$app->request->post('RenderForm');
            $task = new TaskActions(Tasks::findOne($id), Yii::$app->user->id);
            $task->complete($attributes);
        }
        return $this->goHome();
    }

    /**
     *  заказчик удаляет задание.
     *
     * @param int $id id задания
     *
     * @return mixed
     */
    public function actionCancel($id)
    {
        $task = new TaskActions(Tasks::findOne($id), Yii::$app->user->id);
        $task->cancelTask();

        return $this->goHome();
    }

    /**
     *  исполнитель отказывается.
     *
     * @param int $id id задания
     *
     * @return mixed
     */
    public function actionRefuse($id)
    {
        $task = new TaskActions(Tasks::findOne($id), Yii::$app->user->id);
        $task->refuse();

        return $this->goHome();
    }
}
