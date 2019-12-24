namespace app\<?=$cModule?>\controller<?=$cLayer?>;

use <?=$cBase?>;
use app\<?=$mModule?>\model<?=$mLayer?>\<?=$modelName?><?=$modelNSAlias?>;
use app\<?=$vModule?>\validate<?=$vLayer?>\<?=$validateName?><?=$validateNSAlias?>;
use app\common\ErrorCode;
use think\exception\ValidateException;
use <?=$businessException?>;
use think\facade\Db;
use think\facade\Log;
use think\Request;
use think\Response;

class <?=$controllerName?> extends <?=$cBaseName?>

{
    <?php if(!$baseControllerCorrect){ ?>

    const LIST_ALL_DATA = 'list_all';
    <?php }?>

    /**
     * 列表
     *
     * @return Response
     */
    public function index()
    {
        $page     = $this->request->param('page/d', 1);
        if($page < 1){
            return $this->error(ErrorCode::ARGS_WRONG, '页码不能小于1');
        }
        $listRows = $this->request->param('listRows/d', $this->app->config->get('business.pagination.list_rows', 10));
        if($listRows <= 0){
            return $this->error(ErrorCode::ARGS_WRONG, '分页大小不能小于0');
        }
        $maxRows = $this->app->config->get('business.pagination.list_max_rows', 100);
        if($listRows > $maxRows){
            return $this->error(ErrorCode::ARGS_WRONG, '分页大小不能大于' . $maxRows);
        }
        $option   = $this->request->param('option/s', '');
        if($option){
            <?php if(function_exists('\\app\\checkListOption')){ ?>

            list($action, $isValid) = \app\checkListOption($option);
            if(!$isValid){
                return $this->error(ErrorCode::ARGS_WRONG, 'option参数校验失败');
            }
            <?php } ?>

        }

        $query = <?=$modelAlias?>::withSearch(
            <?=$modelAlias?>::$searchFields,
            $this->request->param()
        );

        $query->order($this->request->param('sort',  ''))->with($this->request->param('with', ''));

        $total = $query->count();

        if(!isset($action) || $action != self::LIST_ALL_DATA){
            $query->page($page, $listRows);
        }

        $hiddenFields = [<?=$hiddenFieldStr?>];
        <?php if(function_exists('\\app\\getHiddenFields')){ ?>

        $hiddenFields = \app\getHiddenFields($hiddenFields);
        <?php } ?>

        $list  = $query->hidden($hiddenFields)->select();
        return $this->success([
            'list'       => $list,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'listRows' => $listRows,
            ],
        ]);
    }

    /**
     * 新增
     *
     * @return Response
     * @throws ValidateException
     */
    public function save()
    {
        $data = $this->request->only([
            <?=$fieldStr?>
        ]);

        $this->validate($data, <?=$validateAlias?>::class);

        try {
            $<?=$modelInstance?> = <?=$modelAlias?>::create($data);
            return $this->success('保存成功', [
                'info' => $<?=$modelInstance?>

            ]);
        } catch (<?=$businessExceptionName?> $e) {
            return $this->error(ErrorCode::getErrorCode($e, ErrorCode::DB_WRONG), '保存失败', ErrorCode::getErrorMessage($e));
        }
    }

    /**
     * 查看详情
     *
     * @param  int  $id
     * @param  array  $with 关联模型
     * @param  array  $readBy 查询条件
     * @return Response
     */
    public function read(int $id = 0, array $with=null, array $readBy = null)
    {
        if (empty($readBy) && (!$id || (int) $id <= 0)) {
            return $this->error(ErrorCode::ARGS_WRONG, '参数错误');
        }
        if ($id) {
            $<?=$modelInstance?> = <?=$modelAlias?>::with($with)->find((int) $id);
        } else {
            $<?=$modelInstance?> = <?=$modelAlias?>::with($with)->withSearch(<?=$modelAlias?>::$searchFields, $readBy)->find();
        }
        if (!$<?=$modelInstance?>) {
            return $this->error(ErrorCode::DATA_NOT_FOUND, '数据不存在');
        }
        return $this->success($<?=$modelInstance?>);
    }

    /**
     * 更新
     *
     * @param  int  $id
     * @return Response
     */
    public function update(int $id = 0)
    {
        if ((!$id || (int) $id <= 0)) {
            return $this->error(ErrorCode::ARGS_WRONG, '参数错误');
        }
        $<?=$modelInstance?> = <?=$modelAlias?>::find((int) $id);
        if (!$<?=$modelInstance?>) {
            return $this->error(ErrorCode::DATA_NOT_FOUND, '数据不存在');
        }
        $data = $this->request->only([
            <?=$updateFieldStr?>
        ]);

        $this->validate($data, <?=$validateAlias?>::class);

        $<?=$modelInstance?>->readonly([<?=$readonlyFieldStr?>])->appendData($data, true);
        if (empty($<?=$modelInstance?>->getChangedData())) {
            return $this->success('数据未变化');
        }

        try {
            $<?=$modelInstance?>->save();
            return $this->success('保存成功', [
                'info' => $<?=$modelInstance?>

            ]);
        } catch (<?=$businessExceptionName?> $e) {
            return $this->error(ErrorCode::getErrorCode($e, ErrorCode::DB_WRONG), '保存失败', ErrorCode::getErrorMessage($e));
        }
    }

    /**
     * 删除
     *
     * @param  int|array  $id
     * @return Response
     */
    public function delete($id)
    {
        if (empty($id)) {
            return $this->error(ErrorCode::ARGS_WRONG, '参数错误');
        }
        if (!is_array($id)){
            if( (int) $id < 0){
                return $this->error(ErrorCode::ARGS_WRONG, '参数错误');
            }
            $id = [ (int)$id ];
        }
        $list = <?=$modelAlias?>::select($id);
        if ($list->isEmpty()) {
            return $this->error(ErrorCode::DATA_NOT_FOUND, '数据不存在');
        }
        $success = [];
        $error   = [];
        /** @var <?=$modelAlias?> $item */
        foreach ($list as $item) {
            // 多个删除不互相影响
            $pkID = $item->{<?=$modelAlias?>::PRIMARY_KEY};
            try {
                Db::startTrans();
                $item->tryToDelete();
                Db::commit();
                $success[] = $pkID;
            } catch (\Exception $e) {
                Db::rollback();
                $error[] = ErrorCode::getErrorMessage($e, [
                    'id' => $pkID
                ]);
            }
        }
        return $this->success(count($success) ? '删除成功' : '删除失败', [
            'error'   => $error,
            'success' => $success,
        ]);
    }

}
