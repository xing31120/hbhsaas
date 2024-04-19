<?php
namespace app\common\model\basic;
use think\Model;
use Elasticsearch\ClientBuilder;

//Elasticsearch模型
class Es extends Model{

    public function __construct(){
        parent::__construct();
        $params = [
            'host'=>env('es.es_host','127.0.0.1'),
            'port'=>env('es.es_port','9200'),
            'scheme'=>env('es.es_scheme','http')
        ];
        $this->client = ClientBuilder::create()->setHosts($params)->build();
    }

    public function es(){
        $r = $this->delete_index();
        $r = $this->create_index();  //1.创建索引
        $r = $this->create_mappings(); //2.创建文档模板
        $r = $this->get_mapping();
        $docs = [];
        $docs[] = ['id'=>1,'name'=>'小明','profile'=>'我做的ui界面强无敌。','age'=>23];
        $docs[] = ['id'=>2,'name'=>'小张','profile'=>'我的php代码无懈可击。','age'=>24];
        $docs[] = ['id'=>3,'name'=>'小王','profile'=>'C的生活，快乐每一天。','age'=>29];
        $docs[] = ['id'=>4,'name'=>'小赵','profile'=>'就没有我做不出的前端页面。','age'=>26];
        $docs[] = ['id'=>5,'name'=>'小吴','profile'=>'php是最好的语言。','age'=>21];
        $docs[] = ['id'=>6,'name'=>'小翁','profile'=>'别烦我，我正在敲bug呢！','age'=>25];
        $docs[] = ['id'=>7,'name'=>'小杨','profile'=>'为所欲为，不行就删库跑路','age'=>27];
        foreach ($docs as $k => $v) {
            $r = $this->add_doc($v['id'],$v);   //3.添加文档
        }
        return 1;
    }

    public function esSearch(){
        $data = input();
        $kw = isset($data['kw']) ? $data['kw'] : "";
        $r = $this->search_doc($kw);  //4.搜索结果
        echo '<pre>';
        print_r($r);
        echo '</pre>';
        return 1;
    }

    // 创建索引
    public function create_index($index_name = 'test_ik') { // 只能创建一次
        $params = [
            'index' => $index_name,
            'body' => [
                'settings' => [
                    'number_of_shards' => 5,
                    'number_of_replicas' => 0
                ]
            ]
        ];

        try {
            return $this->client->indices()->create($params);
        } catch (Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
            $msg = $e->getMessage();
            $msg = json_decode($msg,true);
            return $msg;
        }
    }

    // 删除索引
    public function delete_index($index_name = 'test_ik') {
        $params = ['index' => $index_name];
        if($this->client->indices()->exists($params)){
            $response = $this->client->indices()->delete($params);
            return $response;
        }else{
            return true;
        }
    }

    // 创建文档模板
    public function create_mappings($index_name = 'test_ik') {
        $params = [
            'index' => $index_name,
            'body' => [
                '_source' => [
                    'enabled' => true
                ],
                'properties' => [
                    'id' => [
                        'type' => 'integer', // 整型
                        'index' => false,
                    ],
                    'name' => [
                        'type' => 'text', // 字符串型
                        'index' => true, // 全文搜索
                        'analyzer' => 'ik_max_word'
                    ],
                    'profile' => [
                        'type' => 'text',
                        'index' => true,
                        'analyzer' => 'ik_max_word'
                    ],
                    'age' => [
                        'type' => 'integer',
                    ],
                ]
            ]
        ];
        $response = $this->client->indices()->putMapping($params);
        return $response;
    }

    // 查看映射
    public function get_mapping($index_name = 'test_ik') {
        $params = [
            'index' => $index_name,
        ];
        $response = $this->client->indices()->getMapping($params);
        return $response;
    }

    // 添加文档
    public function add_doc($id,$doc,$index_name = 'test_ik') {
        $params = [
            'index' => $index_name,
            'id' => $id,
            'body' => $doc
        ];
        $response = $this->client->index($params);
        return $response;
    }

    // 判断文档存在
    public function exists_doc($id = 1,$index_name = 'test_ik') {
        $params = [
            'index' => $index_name,
            'id' => $id
        ];
        $response = $this->client->exists($params);
        return $response;
    }


    // 获取文档
    public function get_doc($id = 1,$index_name = 'test_ik') {
        $params = [
            'index' => $index_name,
            'id' => $id
        ];
        $response = $this->client->get($params);
        return $response;
    }

    // 更新文档
    public function update_doc($id = 1,$index_name = 'test_ik',$update = ['name' => '姓名']) {
        // 可以灵活添加新字段,最好不要乱添加
        $params = [
            'index' => $index_name,
            'id' => $id,
            'body' => ['doc' => $update]
        ];
        $response = $this->client->update($params);
        return $response;
    }

    // 删除文档
    public function delete_doc($id = 1,$index_name = 'test_ik') {
        $params = [
            'index' => $index_name,
            'id' => $id
        ];

        $response = $this->client->delete($params);
        return $response;
    }

    // 查询文档 (分页，排序，权重，过滤)
    public function search_doc($keywords = "",$index_name = "test_ik",$search_params = ['profile','name'],$sort = ['age'=>['order'=>'desc']],$from = 0,$size = 20) {
        $boost = count($search_params);//最高权重
        $i = 0;
        $should = [];//搜索项设置
        foreach ($search_params as $v) {
            $should[] = ['match'=>[$v => [
                'query' => $keywords,
                'boost' => $boost - intval($i++),
            ]]];
        }
        $params = ['index' => $index_name];
        if(!empty($keywords)){
            $params['body'] = [
                'query' => ['bool' => ['should' => $should]],
                'sort' => $sort , 'from' => $from, 'size' => $size
            ];
        }
        $results = $this->client->search($params);
        return $results;
    }

}
