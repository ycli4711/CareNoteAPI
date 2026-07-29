import { Head } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowUpRight,
    Boxes,
    ClipboardList,
    PackageCheck,
    ShoppingBag,
    UsersRound,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

const overviewCards = [
    {
        title: '今日订单',
        value: '128',
        description: '较昨日 +12.4%',
        icon: ShoppingBag,
        tone: 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
    },
    {
        title: '活跃用户',
        value: '2,846',
        description: '本周新增 236 人',
        icon: UsersRound,
        tone: 'bg-sky-500/10 text-sky-600 dark:text-sky-300',
    },
    {
        title: '上架商品',
        value: '634',
        description: '库存预警 18 件',
        icon: Boxes,
        tone: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
    },
    {
        title: '履约完成率',
        value: '96.8%',
        description: '近 7 天稳定',
        icon: PackageCheck,
        tone: 'bg-rose-500/10 text-rose-600 dark:text-rose-300',
    },
];

const taskItems = [
    { title: '审核新品上架申请', meta: '商品中心', status: '待处理' },
    { title: '处理 3 笔退款售后', meta: '订单中心', status: '优先' },
    { title: '补全会员等级权益文案', meta: '用户运营', status: '进行中' },
    { title: '核对本周库存预警清单', meta: '仓储协同', status: '待处理' },
];

const activityItems = [
    '运营创建了春夏连衣裙专题',
    '系统同步了 48 条库存变更',
    '客服关闭了 12 个售后工单',
    '管理员更新了安全策略',
];

export default function Dashboard() {
    return (
        <>
            <Head title="控制台" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="overflow-hidden rounded-3xl border bg-[linear-gradient(135deg,var(--sidebar-accent)_0%,var(--background)_46%,rgba(245,158,11,0.12)_100%)] p-6 shadow-sm">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-2xl space-y-3">
                            <Badge variant="secondary" className="rounded-full">
                                Summer Closet Admin
                            </Badge>
                            <div>
                                <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                                    控制台
                                </h1>
                                <p className="mt-3 text-sm leading-6 text-muted-foreground md:text-base">
                                    聚合订单、商品、库存和用户运营状态，作为后台管理页面的统一入口。
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <div className="rounded-2xl border bg-background/70 p-4 backdrop-blur">
                                <div className="text-muted-foreground">
                                    本月 GMV
                                </div>
                                <div className="mt-2 text-2xl font-semibold">
                                    ¥428k
                                </div>
                            </div>
                            <div className="rounded-2xl border bg-background/70 p-4 backdrop-blur">
                                <div className="text-muted-foreground">
                                    转化率
                                </div>
                                <div className="mt-2 text-2xl font-semibold">
                                    8.7%
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {overviewCards.map((item) => (
                        <Card key={item.title} className="overflow-hidden">
                            <CardHeader className="flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardDescription>
                                        {item.title}
                                    </CardDescription>
                                    <CardTitle className="mt-3 text-3xl">
                                        {item.value}
                                    </CardTitle>
                                </div>
                                <div className={`rounded-2xl p-3 ${item.tone}`}>
                                    <item.icon className="size-5" />
                                </div>
                            </CardHeader>
                            <CardContent className="flex items-center gap-2 text-sm text-muted-foreground">
                                <ArrowUpRight className="size-4 text-emerald-500" />
                                {item.description}
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.4fr_0.8fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>待办事项</CardTitle>
                            <CardDescription>
                                后续接入真实接口后，可替换为订单、商品、售后和系统任务。
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="divide-y rounded-2xl border">
                                {taskItems.map((item) => (
                                    <div
                                        key={item.title}
                                        className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="flex items-start gap-3">
                                            <div className="rounded-xl bg-muted p-2">
                                                <ClipboardList className="size-4" />
                                            </div>
                                            <div>
                                                <div className="font-medium">
                                                    {item.title}
                                                </div>
                                                <div className="mt-1 text-sm text-muted-foreground">
                                                    {item.meta}
                                                </div>
                                            </div>
                                        </div>
                                        <Badge
                                            variant={
                                                item.status === '优先'
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                        >
                                            {item.status}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>最近动态</CardTitle>
                            <CardDescription>
                                用于承载后台审计、运营动作和系统通知。
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {activityItems.map((item) => (
                                <div key={item} className="flex gap-3">
                                    <div className="mt-0.5 rounded-full bg-primary/10 p-2 text-primary">
                                        <AlertCircle className="size-4" />
                                    </div>
                                    <div>
                                        <div className="text-sm font-medium">
                                            {item}
                                        </div>
                                        <div className="mt-1 text-xs text-muted-foreground">
                                            刚刚更新
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: '控制台',
            href: dashboard(),
        },
    ],
};
