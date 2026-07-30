import { Head, router, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowDownToLine,
    ArrowUpFromLine,
    CheckCircle2,
    CircleHelp,
    ClipboardList,
    Clock3,
    Gauge,
    KeyRound,
    Layers3,
    PencilLine,
    Route,
    Save,
    Trash2,
    XCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useId, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Channel = {
    id: string;
    code: string;
    name: string;
    provider_type: string;
    has_api_key: boolean;
    api_key_masked: string | null;
    base_url: string;
    timeout: number;
    enabled: boolean;
};

type SceneModel = {
    id: string;
    ai_channel_id: string;
    channel_name: string | null;
    model: string;
    priority: number;
    enabled: boolean;
};

type Scene = {
    code: string;
    name: string;
    description: string | null;
    routes: SceneModel[];
};

type Quota = {
    scene: string;
    name: string;
    default_limit: number;
    early_bird_limit: number;
};

type ReferralReward = {
    code: string;
    name: string;
    scene: string;
    scene_name: string;
    inviter_amount: number;
    invitee_amount: number;
};

type MedicationSheetTier = {
    min_invites: number;
    limit: number;
};

type CallLog = {
    id: string;
    scene_code: string;
    channel_code: string | null;
    model: string | null;
    status: 'succeeded' | 'failed';
    attempt: number;
    duration_ms: number | null;
    input_tokens: number | null;
    output_tokens: number | null;
    error_message: string | null;
    created_at: string | null;
};

type Props = {
    channels: Channel[];
    scenes: Scene[];
    quotas: Quota[];
    referralRewards: ReferralReward[];
    medicationSheetTiers: MedicationSheetTier[];
    stats: {
        requests: number;
        succeeded: number;
        failed: number;
        fallbacks: number;
    };
    callLogs: CallLog[];
};

type Tab = 'channels' | 'scenes' | 'quota' | 'logs';

const tabs: Array<{ id: Tab; label: string; icon: LucideIcon }> = [
    { id: 'channels', label: '渠道', icon: KeyRound },
    { id: 'scenes', label: '场景模型', icon: Layers3 },
    { id: 'quota', label: '次数限制', icon: Gauge },
    { id: 'logs', label: '调用日志', icon: ClipboardList },
];

export default function AiManagement(props: Props) {
    const page = usePage();
    const requestedTab = new URLSearchParams(page.url.split('?')[1] ?? '').get(
        'tab',
    ) as Tab | null;
    const [activeTab, setActiveTab] = useState<Tab>(
        requestedTab && tabs.some((tab) => tab.id === requestedTab)
            ? requestedTab
            : 'channels',
    );
    const successRate = props.stats.requests
        ? Math.round((props.stats.succeeded / props.stats.requests) * 100)
        : 0;

    return (
        <>
            <Head title="AI 管理" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border bg-card p-6 shadow-sm">
                    <Badge variant="secondary" className="rounded-full">
                        CareNote AI Control Center
                    </Badge>
                    <div className="mt-3 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight">
                                AI 管理
                            </h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                统一维护渠道、固定场景模型、次数限制和调用记录。
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <StatCard
                                label="今日请求"
                                value={props.stats.requests}
                                icon={Activity}
                            />
                            <StatCard
                                label="成功率"
                                value={`${successRate}%`}
                                icon={CheckCircle2}
                            />
                            <StatCard
                                label="失败"
                                value={props.stats.failed}
                                icon={XCircle}
                            />
                            <StatCard
                                label="自动降级"
                                value={props.stats.fallbacks}
                                icon={Route}
                            />
                        </div>
                    </div>
                </section>

                <nav className="flex gap-2 overflow-x-auto rounded-2xl border bg-card p-2">
                    {tabs.map((tab) => (
                        <Button
                            key={tab.id}
                            type="button"
                            variant={activeTab === tab.id ? 'default' : 'ghost'}
                            onClick={() => setActiveTab(tab.id)}
                        >
                            <tab.icon className="size-4" />
                            {tab.label}
                        </Button>
                    ))}
                </nav>

                {activeTab === 'channels' && (
                    <ChannelsPanel channels={props.channels} />
                )}
                {activeTab === 'scenes' && (
                    <SceneModelsPanel
                        channels={props.channels}
                        scenes={props.scenes}
                    />
                )}
                {activeTab === 'quota' && (
                    <QuotaPanel
                        initialQuotas={props.quotas}
                        initialReferralRewards={props.referralRewards}
                        initialMedicationSheetTiers={props.medicationSheetTiers}
                    />
                )}
                {activeTab === 'logs' && (
                    <LogsPanel logs={props.callLogs} scenes={props.scenes} />
                )}
            </div>
        </>
    );
}

function ChannelsPanel({ channels }: { channels: Channel[] }) {
    const empty = {
        code: '',
        name: '',
        provider_type: 'qwen',
        api_key: '',
        base_url: '',
        timeout: 30,
        enabled: true,
        clear_api_key: false,
    };
    const [editingId, setEditingId] = useState<string | null>(null);
    const [form, setForm] = useState(empty);

    function submit(event: FormEvent) {
        event.preventDefault();
        router.post(
            editingId
                ? `/admin/ai/channels/${editingId}`
                : '/admin/ai/channels',
            form,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditingId(null);
                    setForm(empty);
                },
            },
        );
    }

    return (
        <ManagementGrid
            list={
                <ListCard title="渠道" description="保存供应商地址和密钥。">
                    {channels.map((channel) => (
                        <ItemRow
                            key={channel.id}
                            title={channel.name}
                            subtitle={`${channel.provider_type} · ${channel.base_url}`}
                            enabled={channel.enabled}
                            detail={
                                channel.has_api_key
                                    ? `密钥 ${channel.api_key_masked}`
                                    : '未配置密钥'
                            }
                            onEdit={() => {
                                setEditingId(channel.id);
                                setForm({
                                    code: channel.code,
                                    name: channel.name,
                                    provider_type: channel.provider_type,
                                    api_key: '',
                                    base_url: channel.base_url,
                                    timeout: channel.timeout,
                                    enabled: channel.enabled,
                                    clear_api_key: false,
                                });
                            }}
                            onDelete={() =>
                                destroy(
                                    `/admin/ai/channels/${channel.id}/delete`,
                                    `确定删除渠道「${channel.name}」吗？`,
                                )
                            }
                        />
                    ))}
                    {channels.length === 0 && <EmptyText text="暂无渠道" />}
                </ListCard>
            }
            editor={
                <EditorCard
                    title={editingId ? '编辑渠道' : '新增渠道'}
                    onSubmit={submit}
                >
                    <Field label="渠道名称">
                        <Input
                            value={form.name}
                            onChange={(event) =>
                                setForm({ ...form, name: event.target.value })
                            }
                            required
                        />
                    </Field>
                    <Field label="渠道编码">
                        <Input
                            value={form.code}
                            onChange={(event) =>
                                setForm({ ...form, code: event.target.value })
                            }
                            required
                        />
                    </Field>
                    <Field label="供应商">
                        <FormSelect
                            value={form.provider_type}
                            onChange={(value) =>
                                setForm({ ...form, provider_type: value })
                            }
                            options={[
                                ['qwen', 'Qwen / DashScope'],
                                ['openai', 'OpenAI'],
                            ]}
                        />
                    </Field>
                    <Field
                        label={editingId ? 'API Key（留空不修改）' : 'API Key'}
                    >
                        <Input
                            type="password"
                            value={form.api_key}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    api_key: event.target.value,
                                })
                            }
                        />
                    </Field>
                    <Field label="Base URL">
                        <Input
                            value={form.base_url}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    base_url: event.target.value,
                                })
                            }
                            required
                        />
                    </Field>
                    <Field label="超时（秒）">
                        <Input
                            type="number"
                            min={1}
                            max={300}
                            value={form.timeout}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    timeout: Number(event.target.value),
                                })
                            }
                        />
                    </Field>
                    <CheckboxField
                        label="启用渠道"
                        checked={form.enabled}
                        onChange={(enabled) => setForm({ ...form, enabled })}
                    />
                    {editingId && (
                        <CheckboxField
                            label="清除已保存的 API Key"
                            checked={form.clear_api_key}
                            onChange={(clear_api_key) =>
                                setForm({ ...form, clear_api_key })
                            }
                        />
                    )}
                    <EditorActions
                        editing={editingId !== null}
                        onCancel={() => {
                            setEditingId(null);
                            setForm(empty);
                        }}
                    />
                </EditorCard>
            }
        />
    );
}

function SceneModelsPanel({
    channels,
    scenes,
}: {
    channels: Channel[];
    scenes: Scene[];
}) {
    const empty = {
        ai_channel_id: channels[0]?.id ?? '',
        scene_code: scenes[0]?.code ?? '',
        model: '',
        priority: 10,
        enabled: true,
    };
    const [editingId, setEditingId] = useState<string | null>(null);
    const [form, setForm] = useState(empty);

    function submit(event: FormEvent) {
        event.preventDefault();
        router.post(
            editingId
                ? `/admin/ai/scene-models/${editingId}`
                : '/admin/ai/scene-models',
            form,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditingId(null);
                    setForm(empty);
                },
            },
        );
    }

    return (
        <ManagementGrid
            list={
                <ListCard
                    title="场景模型"
                    description="同一场景按优先级自动调用；失败时继续尝试下一模型。"
                >
                    <div className="overflow-hidden rounded-xl border bg-background shadow-xs">
                        <Table className="min-w-[760px]">
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="w-[28%]">
                                        业务场景
                                    </TableHead>
                                    <TableHead>渠道</TableHead>
                                    <TableHead>模型</TableHead>
                                    <TableHead className="w-24 text-center">
                                        优先级
                                    </TableHead>
                                    <TableHead className="w-24 text-center">
                                        状态
                                    </TableHead>
                                    <TableHead className="w-28 text-right">
                                        操作
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {scenes.flatMap((scene) =>
                                    scene.routes.length > 0
                                        ? scene.routes.map(
                                              (route, routeIndex) => (
                                                  <TableRow
                                                      key={route.id}
                                                      className={
                                                          routeIndex === 0
                                                              ? 'border-t border-border/80'
                                                              : ''
                                                      }
                                                  >
                                                      {routeIndex === 0 && (
                                                          <TableCell
                                                              rowSpan={
                                                                  scene.routes
                                                                      .length
                                                              }
                                                              className="border-r bg-muted/15 align-top whitespace-normal"
                                                          >
                                                              <div className="flex gap-3 py-1">
                                                                  <div className="flex size-8 shrink-0 items-center justify-center rounded-lg border bg-background text-muted-foreground shadow-xs">
                                                                      <Layers3 className="size-4" />
                                                                  </div>
                                                                  <div className="min-w-0">
                                                                      <div className="font-semibold text-foreground">
                                                                          {
                                                                              scene.name
                                                                          }
                                                                      </div>
                                                                      {scene.description && (
                                                                          <p className="mt-1 max-w-60 text-xs leading-5 text-muted-foreground">
                                                                              {
                                                                                  scene.description
                                                                              }
                                                                          </p>
                                                                      )}
                                                                  </div>
                                                              </div>
                                                          </TableCell>
                                                      )}
                                                      <TableCell>
                                                          <Badge
                                                              variant="outline"
                                                              className="gap-1.5 rounded-md bg-background font-normal"
                                                          >
                                                              <span className="size-1.5 rounded-full bg-emerald-500" />
                                                              {route.channel_name ??
                                                                  '-'}
                                                          </Badge>
                                                      </TableCell>
                                                      <TableCell>
                                                          <code className="rounded-md bg-muted px-2 py-1 font-mono text-[13px] font-medium text-foreground">
                                                              {route.model}
                                                          </code>
                                                      </TableCell>
                                                      <TableCell className="text-center tabular-nums">
                                                          <span className="inline-flex size-7 items-center justify-center rounded-full border bg-background text-xs font-semibold shadow-xs">
                                                              {route.priority}
                                                          </span>
                                                      </TableCell>
                                                      <TableCell className="text-center">
                                                          <StatusBadge
                                                              enabled={
                                                                  route.enabled
                                                              }
                                                          />
                                                      </TableCell>
                                                      <TableCell className="text-right">
                                                          <div className="flex justify-end gap-0.5">
                                                              <Button
                                                                  type="button"
                                                                  size="sm"
                                                                  variant="ghost"
                                                                  className="h-8 gap-1.5 px-2.5 text-muted-foreground hover:text-foreground"
                                                                  onClick={() => {
                                                                      setEditingId(
                                                                          route.id,
                                                                      );
                                                                      setForm({
                                                                          ai_channel_id:
                                                                              route.ai_channel_id,
                                                                          scene_code:
                                                                              scene.code,
                                                                          model: route.model,
                                                                          priority:
                                                                              route.priority,
                                                                          enabled:
                                                                              route.enabled,
                                                                      });
                                                                  }}
                                                              >
                                                                  <PencilLine className="size-3.5" />
                                                                  编辑
                                                              </Button>
                                                              <Button
                                                                  type="button"
                                                                  size="icon"
                                                                  variant="ghost"
                                                                  className="size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                                                  aria-label={`删除模型 ${route.model}`}
                                                                  onClick={() =>
                                                                      destroy(
                                                                          `/admin/ai/scene-models/${route.id}/delete`,
                                                                          `确定删除模型「${route.model}」吗？`,
                                                                      )
                                                                  }
                                                              >
                                                                  <Trash2 className="size-4" />
                                                              </Button>
                                                          </div>
                                                      </TableCell>
                                                  </TableRow>
                                              ),
                                          )
                                        : [
                                              <TableRow key={scene.code}>
                                                  <TableCell className="border-r bg-muted/15 whitespace-normal">
                                                      <div className="flex items-center gap-3">
                                                          <div className="flex size-8 items-center justify-center rounded-lg border bg-background text-muted-foreground shadow-xs">
                                                              <Layers3 className="size-4" />
                                                          </div>
                                                          <span className="font-semibold">
                                                              {scene.name}
                                                          </span>
                                                      </div>
                                                  </TableCell>
                                                  <TableCell
                                                      colSpan={5}
                                                      className="py-6 text-center text-muted-foreground"
                                                  >
                                                      暂无模型配置
                                                  </TableCell>
                                              </TableRow>,
                                          ],
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </ListCard>
            }
            editor={
                <EditorCard
                    title={editingId ? '编辑场景模型' : '新增场景模型'}
                    onSubmit={submit}
                >
                    <Field label="渠道">
                        <FormSelect
                            value={form.ai_channel_id}
                            onChange={(ai_channel_id) =>
                                setForm({ ...form, ai_channel_id })
                            }
                            options={channels.map((channel) => [
                                channel.id,
                                channel.name,
                            ])}
                        />
                    </Field>
                    <Field label="场景">
                        <FormSelect
                            value={form.scene_code}
                            onChange={(scene_code) =>
                                setForm({ ...form, scene_code })
                            }
                            options={scenes.map((scene) => [
                                scene.code,
                                scene.name,
                            ])}
                        />
                    </Field>
                    <Field label="模型名">
                        <Input
                            value={form.model}
                            placeholder="例如 qwen-plus 或 gpt-4.1-mini"
                            onChange={(event) =>
                                setForm({ ...form, model: event.target.value })
                            }
                            required
                        />
                    </Field>
                    <Field label="优先级">
                        <Input
                            type="number"
                            min={1}
                            max={65535}
                            value={form.priority}
                            onChange={(event) =>
                                setForm({
                                    ...form,
                                    priority: Number(event.target.value),
                                })
                            }
                        />
                    </Field>
                    <CheckboxField
                        label="启用"
                        checked={form.enabled}
                        onChange={(enabled) => setForm({ ...form, enabled })}
                    />
                    <EditorActions
                        editing={editingId !== null}
                        onCancel={() => {
                            setEditingId(null);
                            setForm(empty);
                        }}
                    />
                </EditorCard>
            }
        />
    );
}

function QuotaPanel({
    initialQuotas,
    initialReferralRewards,
    initialMedicationSheetTiers,
}: {
    initialQuotas: Quota[];
    initialReferralRewards: ReferralReward[];
    initialMedicationSheetTiers: MedicationSheetTier[];
}) {
    const [quotas, setQuotas] = useState(initialQuotas);
    const [referralRewards, setReferralRewards] = useState(
        initialReferralRewards,
    );
    const [medicationSheetTiers, setMedicationSheetTiers] = useState(
        initialMedicationSheetTiers,
    );

    return (
        <Card className="gap-0 overflow-hidden py-0">
            <CardHeader className="flex flex-row items-start justify-between border-b p-6">
                <div>
                    <CardTitle>AI 使用次数</CardTitle>
                    <CardDescription className="mt-1">
                        配置用户每月可以使用多少次，以及邀请好友后增加多少次。
                    </CardDescription>
                </div>
                <Badge
                    variant="outline"
                    className="rounded-full bg-background px-3 font-normal text-muted-foreground"
                >
                    每月重置
                </Badge>
            </CardHeader>
            <CardContent className="space-y-8 p-6">
                <Alert className="bg-muted/20">
                    <CircleHelp />
                    <AlertTitle>用户最终能用多少次？</AlertTitle>
                    <AlertDescription>
                        普通或早鸟基础次数，加上用户已经获得的邀请和成长奖励；用药单导入还会按照成功邀请人数自动提升。
                    </AlertDescription>
                </Alert>
                <section>
                    <div className="mb-4">
                        <h3 className="font-semibold">1. 每月基础次数</h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            所有用户先获得基础次数，历史早鸟用户使用单独额度。
                        </p>
                    </div>
                    <div className="overflow-hidden rounded-xl border">
                        <Table className="min-w-[640px]">
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead>功能</TableHead>
                                    <TableHead className="w-52">
                                        普通用户每月
                                    </TableHead>
                                    <TableHead className="w-52">
                                        早鸟用户每月
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {quotas.map((quota, index) => (
                                    <TableRow key={quota.scene}>
                                        <TableCell className="font-medium">
                                            {quota.name}
                                        </TableCell>
                                        <TableCell>
                                            <div className="relative w-36">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    value={quota.default_limit}
                                                    className="pr-10 text-right font-medium tabular-nums"
                                                    onChange={(event) =>
                                                        setQuotas((current) =>
                                                            current.map(
                                                                (
                                                                    item,
                                                                    itemIndex,
                                                                ) =>
                                                                    itemIndex ===
                                                                    index
                                                                        ? {
                                                                              ...item,
                                                                              default_limit:
                                                                                  Number(
                                                                                      event
                                                                                          .target
                                                                                          .value,
                                                                                  ),
                                                                          }
                                                                        : item,
                                                            ),
                                                        )
                                                    }
                                                />
                                                <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                                    次/月
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="relative w-36">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    value={
                                                        quota.early_bird_limit
                                                    }
                                                    className="pr-10 text-right font-medium tabular-nums"
                                                    onChange={(event) =>
                                                        setQuotas((current) =>
                                                            current.map(
                                                                (
                                                                    item,
                                                                    itemIndex,
                                                                ) =>
                                                                    itemIndex ===
                                                                    index
                                                                        ? {
                                                                              ...item,
                                                                              early_bird_limit:
                                                                                  Number(
                                                                                      event
                                                                                          .target
                                                                                          .value,
                                                                                  ),
                                                                          }
                                                                        : item,
                                                            ),
                                                        )
                                                    }
                                                />
                                                <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                                    次/月
                                                </span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </section>
            </CardContent>
            <CardContent className="space-y-8 border-t p-6">
                <section>
                    <div className="mb-4">
                        <h3 className="font-semibold">2. 邀请好友奖励</h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            每位好友的每个里程碑只奖励一次，奖励会增加到每月次数中。
                        </p>
                    </div>
                    <div className="divide-y overflow-hidden rounded-xl border">
                        {referralRewards.map((reward, index) => (
                            <div
                                key={reward.code}
                                className="flex flex-col gap-3 px-4 py-4 xl:flex-row xl:items-center"
                            >
                                <div className="w-48 shrink-0 font-medium">
                                    {reward.name}后
                                </div>
                                <div className="flex flex-1 flex-wrap items-center gap-2 text-sm">
                                    <span>邀请人获得</span>
                                    <Badge
                                        variant="secondary"
                                        className="rounded-md"
                                    >
                                        {reward.scene_name}
                                    </Badge>
                                    <div className="relative w-24">
                                        <Input
                                            type="number"
                                            min={0}
                                            value={reward.inviter_amount}
                                            className="pr-8 text-right font-medium tabular-nums"
                                            onChange={(event) =>
                                                setReferralRewards((current) =>
                                                    current.map(
                                                        (item, itemIndex) =>
                                                            itemIndex === index
                                                                ? {
                                                                      ...item,
                                                                      inviter_amount:
                                                                          Number(
                                                                              event
                                                                                  .target
                                                                                  .value,
                                                                          ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        />
                                        <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                            次
                                        </span>
                                    </div>
                                    <span className="ml-2 text-muted-foreground">
                                        好友获得
                                    </span>
                                    <div className="relative w-24">
                                        <Input
                                            type="number"
                                            min={0}
                                            value={reward.invitee_amount}
                                            className="pr-8 text-right font-medium tabular-nums"
                                            onChange={(event) =>
                                                setReferralRewards((current) =>
                                                    current.map(
                                                        (item, itemIndex) =>
                                                            itemIndex === index
                                                                ? {
                                                                      ...item,
                                                                      invitee_amount:
                                                                          Number(
                                                                              event
                                                                                  .target
                                                                                  .value,
                                                                          ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        />
                                        <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                            次
                                        </span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
                <section className="border-t pt-8">
                    <div className="mb-4">
                        <h3 className="font-semibold">3. 用药单导入邀请阶梯</h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            没有达到阶梯时使用上面的基础次数，达到后自动提升。
                        </p>
                    </div>
                    <div className="divide-y overflow-hidden rounded-xl border">
                        {medicationSheetTiers.map((tier, index) => (
                            <div
                                key={tier.min_invites}
                                className="flex flex-wrap items-center gap-2 px-4 py-4 text-sm"
                            >
                                <span>成功邀请至少</span>
                                <strong className="text-base">
                                    {tier.min_invites}
                                </strong>
                                <span>人后，用药单导入每月可以使用</span>
                                <div className="relative w-28">
                                    <Input
                                        type="number"
                                        min={0}
                                        value={tier.limit}
                                        className="pr-8 text-right font-medium tabular-nums"
                                        onChange={(event) =>
                                            setMedicationSheetTiers((current) =>
                                                current.map(
                                                    (item, itemIndex) =>
                                                        itemIndex === index
                                                            ? {
                                                                  ...item,
                                                                  limit: Number(
                                                                      event
                                                                          .target
                                                                          .value,
                                                                  ),
                                                              }
                                                            : item,
                                                ),
                                            )
                                        }
                                    />
                                    <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">
                                        次
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </CardContent>
            <CardFooter className="justify-between border-t bg-muted/10 px-6 py-4">
                <p className="text-xs text-muted-foreground">
                    保存后只影响新的 AI 请求，不会清空用户本月已使用次数。
                </p>
                <Button
                    type="button"
                    onClick={() =>
                        router.post(
                            '/admin/ai/quota',
                            {
                                quotas,
                                referral_rewards: referralRewards,
                                medication_sheet_tiers: medicationSheetTiers,
                            },
                            { preserveScroll: true },
                        )
                    }
                >
                    <Save className="size-4" />
                    保存次数限制
                </Button>
            </CardFooter>
        </Card>
    );
}

function LogsPanel({ logs, scenes }: { logs: CallLog[]; scenes: Scene[] }) {
    const sceneNames = new Map(scenes.map((scene) => [scene.code, scene.name]));

    return (
        <Card className="gap-0 overflow-hidden py-0">
            <CardHeader className="flex flex-row items-start justify-between border-b bg-muted/10 p-6">
                <div>
                    <CardTitle>调用日志</CardTitle>
                    <CardDescription className="mt-1">
                        跟踪模型调用、降级尝试与资源消耗。
                    </CardDescription>
                </div>
                <Badge
                    variant="outline"
                    className="rounded-full bg-background px-3 font-normal text-muted-foreground"
                >
                    最近 50 次
                </Badge>
            </CardHeader>
            <CardContent className="p-0">
                {logs.length === 0 ? (
                    <div className="flex min-h-64 items-center justify-center px-6 py-12">
                        <div className="max-w-sm text-center">
                            <div className="mx-auto flex size-12 items-center justify-center rounded-2xl border bg-muted/30 text-muted-foreground shadow-xs">
                                <ClipboardList className="size-5" />
                            </div>
                            <h3 className="mt-4 font-semibold">
                                还没有调用记录
                            </h3>
                            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                模型被业务场景调用后，这里会显示执行结果、Token
                                消耗和响应耗时。
                            </p>
                        </div>
                    </div>
                ) : (
                    <Table className="min-w-[980px]">
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead>调用时间</TableHead>
                                <TableHead>业务场景</TableHead>
                                <TableHead>渠道与模型</TableHead>
                                <TableHead className="text-center">
                                    尝试
                                </TableHead>
                                <TableHead>Token 用量</TableHead>
                                <TableHead>响应耗时</TableHead>
                                <TableHead>结果</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.map((log) => (
                                <TableRow key={log.id}>
                                    <TableCell className="text-muted-foreground">
                                        <div className="flex items-center gap-2">
                                            <Clock3 className="size-3.5" />
                                            {log.created_at}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium">
                                            {sceneNames.get(log.scene_code) ??
                                                log.scene_code}
                                        </div>
                                        <div className="mt-0.5 font-mono text-[11px] text-muted-foreground">
                                            {log.scene_code}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant="outline"
                                                className="rounded-md font-normal"
                                            >
                                                {log.channel_code ?? '-'}
                                            </Badge>
                                            <code className="font-mono text-[12px] font-medium">
                                                {log.model ?? '-'}
                                            </code>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <span className="inline-flex size-7 items-center justify-center rounded-full bg-muted text-xs font-semibold tabular-nums">
                                            {log.attempt}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-3 text-xs tabular-nums">
                                            <span className="flex items-center gap-1 text-muted-foreground">
                                                <ArrowDownToLine className="size-3.5" />
                                                {log.input_tokens ?? 0}
                                            </span>
                                            <span className="flex items-center gap-1 font-medium">
                                                <ArrowUpFromLine className="size-3.5" />
                                                {log.output_tokens ?? 0}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {log.duration_ms === null
                                            ? '-'
                                            : `${log.duration_ms} ms`}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            enabled={log.status === 'succeeded'}
                                        />
                                        {log.error_message && (
                                            <div className="mt-1 max-w-xs truncate text-xs text-destructive">
                                                {log.error_message}
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}

function ManagementGrid({
    list,
    editor,
}: {
    list: ReactNode;
    editor: ReactNode;
}) {
    return (
        <div className="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.5fr)]">
            {list}
            {editor}
        </div>
    );
}

function ListCard({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">{children}</CardContent>
        </Card>
    );
}

function EditorCard({
    title,
    onSubmit,
    children,
}: {
    title: string;
    onSubmit: (event: FormEvent) => void;
    children: ReactNode;
}) {
    return (
        <Card className="h-fit">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <form className="space-y-4" onSubmit={onSubmit}>
                    {children}
                </form>
            </CardContent>
        </Card>
    );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
        </div>
    );
}

function FormSelect({
    value,
    onChange,
    options,
}: {
    value: string;
    onChange: (value: string) => void;
    options: Array<[string, string]>;
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger className="w-full">
                <SelectValue placeholder="请选择" />
            </SelectTrigger>
            <SelectContent>
                {options.map(([optionValue, label]) => (
                    <SelectItem key={optionValue} value={optionValue}>
                        {label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function CheckboxField({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    const id = useId();

    return (
        <div className="flex items-center gap-2">
            <Checkbox
                id={id}
                checked={checked}
                onCheckedChange={(value) => onChange(value === true)}
            />
            <Label htmlFor={id}>{label}</Label>
        </div>
    );
}

function EditorActions({
    editing,
    onCancel,
}: {
    editing: boolean;
    onCancel: () => void;
}) {
    return (
        <div className="flex gap-2">
            <Button type="submit">
                <Save className="size-4" />
                {editing ? '保存' : '创建'}
            </Button>
            {editing && (
                <Button type="button" variant="outline" onClick={onCancel}>
                    取消
                </Button>
            )}
        </div>
    );
}

function ItemRow({
    title,
    subtitle,
    detail,
    enabled,
    onEdit,
    onDelete,
}: {
    title: string;
    subtitle: string;
    detail?: string;
    enabled: boolean;
    onEdit: () => void;
    onDelete: () => void;
}) {
    return (
        <div className="flex items-center justify-between gap-4 rounded-xl border p-3">
            <div className="min-w-0">
                <div className="flex items-center gap-2">
                    <span className="truncate font-medium">{title}</span>
                    <StatusBadge enabled={enabled} />
                </div>
                <div className="mt-1 truncate text-xs text-muted-foreground">
                    {subtitle}
                </div>
                {detail && (
                    <div className="mt-1 truncate text-xs text-muted-foreground">
                        {detail}
                    </div>
                )}
            </div>
            <div className="flex shrink-0 gap-1">
                <Button type="button" variant="ghost" onClick={onEdit}>
                    编辑
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    onClick={onDelete}
                >
                    <Trash2 className="size-4" />
                </Button>
            </div>
        </div>
    );
}

function StatusBadge({ enabled }: { enabled: boolean }) {
    return (
        <Badge variant={enabled ? 'secondary' : 'outline'}>
            {enabled ? '启用' : '停用'}
        </Badge>
    );
}

function StatCard({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: string | number;
    icon: LucideIcon;
}) {
    return (
        <div className="rounded-2xl border bg-background/80 p-3">
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <Icon className="size-4" />
                {label}
            </div>
            <div className="mt-2 text-xl font-semibold">{value}</div>
        </div>
    );
}

function EmptyText({ text }: { text: string }) {
    return (
        <div className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
            {text}
        </div>
    );
}

function destroy(url: string, message: string) {
    if (window.confirm(message)) {
        router.post(url, {}, { preserveScroll: true });
    }
}
