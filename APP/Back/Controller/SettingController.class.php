<?php
namespace Back\Controller;

use Think\Controller;
use Think\Page;

class SettingController extends Controller
{
    /**
     * 添加动作
     */
    public function add()
    {
        // 判断是否为POST数据提交
        if (IS_POST) {
            // 数据处理
            $model = D('Setting');
            $result = $model->create();    //创建数据对象

            if (!$result) {
                $this->error('数据添加失败: ' . $model->getError(), U('add'));
            }

            //添加数据
            $result = $model->add();
            if (!$result) {
                $this->error('数据添加失败:' . $model->getError(), U('add'));
            }
            // 成功重定向到list页
            $this->redirect('lists', [], 0);
        } else {
            // 表单展示
            $this->display();
        }
    }


    /**
     * 列表相关动作
     */
    public function lists()
    {
        if (IS_POST) {

            p($_POST);
        } else {
            $m_setting = D('Setting');
            // 分页, 搜索, 排序等
            // 搜索, 筛选, 过滤
            // 判断用户传输的搜索条件, 进行处理
            // $filter 表示用户输入的内容
            // $cond 表示用在模型中查询条件
//        $cond = [];// 初始条件
//        //搜索处理
//        $filter['filter_setting_name'] = I('get.filter_setting_name', '', 'trim');   //获取搜索关键词
//
//        if ($filter['filter_setting_name'] !== '') {
//            //需要搜索的字段setting_key,setting_name,setting_value
//            $fd = explode(',', "setting_key,setting_name,setting_value");
//            $cond['_logic'] = 'or';       //搜索条件之间为or（或）
//            //遍历模糊搜索条件
//            foreach ($fd as $f) {
//                $cond[$f] = ['like', '%' . $filter['filter_setting_name'] . '%'];// 适当考虑索引问题
//            }
//        }
//        // 分配筛选数据, 到模板, 为了展示搜索条件
//        $this->assign('filter', $filter);
//
//        /***********排序**************/
//        // 考虑用户所传递的排序方式和字段
//        $order['field'] = I('get.field', 'setting_sort', 'trim');// 初始排序, 字段
//        $order['type'] = I('get.type', 'asc', 'trim');// 初始排序, 方式
//
//        $sort = [$order['field'] => $order['type']];
//        $this->assign('order', $order);

            /********获取配置项值*********/
            //获取分组的值
            $setting_group = D('SettingGroup')->select();

            //获取配置项的值和属性并将其值遍历处理，将多选框属性的值进行装换数组
            $setting = $m_setting->alias('s')
                ->join("left join __SETTING_TYPE__ st using(setting_type_id)")
                ->Relation(true)->select();

            $setting_rows = [];//定义一个空数组接收分组过后的值
            foreach ($setting as $set) {
                if ($set['setting_type_name'] == 'select-multi') {
                    $set['setting_value_arr'] = explode(',', $set['setting_value']);
                }
                $setting_rows[$set['setting_group_id']][] = $set;
            }
//            p($setting_rows);die;

            $this->assign('setting_rows', $setting_rows);
            $this->assign('setting_group', $setting_group);

//        // 分页
//        $page = I('get.p', '1');// 当前页码
//        $pagesize = 8;// 每页记录数\\
//        // 获取总记录数
//        $count = $model->where($cond)->count();// 合计
//        $t_page = new Page($count, $pagesize);// use Think\Page;
//        // 配置格式
//        $t_page->setConfig('next', '&gt;');
//        $t_page->setConfig('last', '&gt;|');
//        $t_page->setConfig('prev', '&lt;');
//        $t_page->setConfig('first', '|&lt;');
//        $t_page->setConfig('theme', '<div class="col-sm-6 text-left"><ul class="pagination">%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% </ul></div><div class="col-sm-6 text-right">%HEADER%</div>');
//        $t_page->setConfig('header', '显示开始 %FIRST_ROW% 到 %LAST_ROW% 之 %TOTAL_ROW% （总 %TOTAL_PAGE% 页）');
//        // 生成HTML代码
//        $page_html = $t_page->show();
//        $this->assign('page_html', $page_html);
//        $rows = $model->where($cond)->order($sort)->page("$page, $pagesize")->select();
//        $this->assign('rows', $rows);
//

            $this->display();

        }

    }
    ///*
    //修改保存配置项
    //*/
    public function save()
    {
        // 数据处理
        $setting = I('post.setting');
//            p($_POST);die();
        $m_setting = M('Setting');
        $cond['setting_type_name'] = 'select-multi';
        $s_rows = $m_setting->alias('s')
            ->join('left join __SETTING_TYPE__ st Using(setting_type_id)')
            ->where($cond)->getField('setting_id', true);
//        p($s_rows);

        //如果用户没有选择任何多选项目，将数据更新为空
        foreach ($s_rows as $v) {
            if (!isset($setting[$v])) {
                $setting[$v] = '';
            }
        }
//        p($setting);die;
        //循环插入数据
        foreach ($setting as $k=>$v){
            if(is_array($v)){
                $v=implode(',',$v);
            }
            $data['setting_id']=$k;
            $data['setting_value']=$v;
            $m_setting->save($data);
        }

        //删除以前的缓存项
        S(['type' =>'file']);
        foreach ($m_setting->getField('setting_key',true) as $v){
            S($v,null);
        }

        $this->redirect('lists');



    }


    /**
     * 编辑
     */
    public function edit()
    {

        if (IS_POST) {

            $model = D('Setting');
            $result = $model->create();

            if (!$result) {
                $this->error('数据修改失败: ' . $model->getError(), U('edit'));
            }

            $result = $model->save();
            if (!$result) {
                $this->error('数据修改失败:' . $model->getError(), U('edit'));
            }
            // 成功重定向到list页
            $this->redirect('lists', [], 0);

        } else {

            // 获取当前编辑的内容
            $setting_id = I('get.setting_id', '', 'trim');
            $this->assign('row', M('Setting')->find($setting_id));

            // 展示模板
            $this->display();
        }
    }


    /**
     * 批处理
     */
    public function multi()
    {
        // 确定动作
        $operate = I('post.operate', 'delete', 'trim');
        // 确定ID列表
        $selected = I('post.selected', []);

        switch ($operate) {
            case 'delete':
                // 使用in条件, 删除全部的品牌
                $cond = ['setting_id' => ['in', $selected]];
                M('Setting')->where($cond)->delete();
                $this->redirect('lists', [], 0);
                break;
            default:
                # code...
                break;
        }
    }


    /**
     * ajax的相关请求
     */
    public function ajax()
    {
//        p($_REQUEST);
        $operate = I('request.operate', null, 'trim');

        if (is_null($operate)) {
            return;
        }
        //需要搜索的字段setting_key,setting_name,setting_value
        $fd = explode(',', "setting_key,setting_name,setting_value");

        //循环生成多个if判断语句针对ajax异步验证
        foreach ($fd as $v) {
            if ($operate == 'check' . $v) {
                // 验证品牌名称唯一的操作
                // 获取填写的品牌名称
                $title = I('request.' . $v, '');
                $cond[$v] = $title;
                // 判断是否传递了setting_id
                $setting_id = I('request.setting_id', null);
                if (!is_null($setting_id)) {
                    // 存在, 则匹配与当前ID不相同的记录
                    $cond['setting_id'] = ['neq', $setting_id];
                }
                // 获取模型后, 利用条件获取匹配的记录数
                $count = M('Setting')->where($cond)->count();
                // 如果记录数>0, 条件为真, 说明存在记录, 重复, 验证未通过, 响应false
                echo $count ? 'false' : 'true';
            }
        }

    }
}